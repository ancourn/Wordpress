<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Include required files for new menu system
require_once plugin_dir_path(__FILE__) . 'link-mapper.php';
require_once plugin_dir_path(__FILE__) . 'menu-builder.php';
require_once plugin_dir_path(__FILE__) . 'theme-generator.php';

class HTML_WP_Parser {
    private static $imported_pages = [];
    
    public static function process($file, $use_elementor = false, $generate_theme = false) {
        $file_type = wp_check_filetype($file['name']);
        $tmp_path  = $file['tmp_name'];
        
        // Clear the link mapper at the start of processing
        HTML_WP_Link_Mapper::clear_map();
        
        if ($file_type['ext'] === 'zip') {
            $result = self::process_zip($tmp_path, $use_elementor, $generate_theme);
        } elseif ($file_type['ext'] === 'html') {
            $result = self::process_html_file($tmp_path, $file['name'], $use_elementor, $generate_theme);
        } else {
            wp_die('Unsupported file type. Please upload .html or .zip');
        }
        
        // After processing all pages → build menu using new system
        $pages_map = HTML_WP_Link_Mapper::get_page_map();
        if (!empty($pages_map)) {
            HTML_WP_Menu_Builder::create_menu("Imported Site", $pages_map);
        }
        
        return $result;
    }
    private static function process_zip($zip_path, $use_elementor = false, $generate_theme = false) {
        $extract_to = wp_upload_dir()['basedir'] . '/html-importer-tmp/';
        if (!file_exists($extract_to)) wp_mkdir_p($extract_to);
        $zip = new ZipArchive;
        if ($zip->open($zip_path) === TRUE) {
            $zip->extractTo($extract_to);
            $zip->close();
            // Process all HTML files
            $html_files = glob($extract_to . '*.html');
            foreach ($html_files as $html_file) {
                $filename = basename($html_file);
                self::process_html_file($html_file, $filename, $use_elementor, $generate_theme);
            }
            // Process assets
            HTML_WP_Assets::process($extract_to);
            
            // Generate theme if requested
            if ($generate_theme) {
                $theme_slug = HTML_WP_Theme_Generator::generate_theme($html_files, $extract_to);
                if ($theme_slug) {
                    // Optionally activate the theme
                    // HTML_WP_Theme_Generator::activate_theme($theme_slug);
                }
            }
        } else {
            wp_die('Could not unzip file.');
        }
        
        return [
            'success' => true,
            'theme_generated' => $generate_theme,
            'extract_to' => $extract_to
        ];
    }
    
    private static function process_html_file($file_path, $filename, $use_elementor = false, $generate_theme = false) {
        $html = file_get_contents($file_path);
        return self::process_html_content($html, $filename, $use_elementor, $generate_theme);
    }
    
    private static function process_html_content($html, $filename, $use_elementor = false, $generate_theme = false) {
        // Extract title
        preg_match('/<title>(.*?)<\/title>/is', $html, $title_match);
        $title = !empty($title_match[1]) ? sanitize_text_field($title_match[1]) : 'Imported Page';
        // Extract body
        preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $body_match);
        $body_content = !empty($body_match[1]) ? $body_match[1] : $html;
        
        // Handle assets for single file imports
        if (strpos($body_content, 'src=') !== false || strpos($body_content, 'href=') !== false) {
            require_once plugin_dir_path(__FILE__) . 'asset-handler.php';
            // Create a temporary directory for asset processing
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/html_import_temp_' . time();
            if (!file_exists($temp_dir)) {
                mkdir($temp_dir, 0755, true);
            }
            
            $body_content = HTML_WP_Asset_Handler::process_assets($temp_dir, $body_content);
            
            // Clean up temporary directory
            self::cleanup_directory($temp_dir);
        }
        
        // Insert as WordPress Page
        if ($use_elementor && HTML_WP_Elementor::is_elementor_active()) {
            $page_id = HTML_WP_Elementor::create_elementor_page($title, $body_content);
        } else {
            $page_id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => $body_content,
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ]);
        }
        
        // Add page to the link mapper
        if ($page_id && !is_wp_error($page_id)) {
            HTML_WP_Link_Mapper::add_page($filename, $page_id);
            self::$imported_pages[] = $page_id;
            
            // Generate theme if requested (for single HTML files)
            if ($generate_theme) {
                $upload_dir = wp_upload_dir();
                $temp_dir = $upload_dir['basedir'] . '/html_import_temp_' . time();
                if (!file_exists($temp_dir)) {
                    mkdir($temp_dir, 0755, true);
                }
                
                // Create temporary HTML file for theme generation
                $temp_html_file = $temp_dir . '/' . $filename;
                file_put_contents($temp_html_file, $html);
                
                // Generate theme
                $theme_slug = HTML_WP_Theme_Generator::generate_theme([$temp_html_file], $temp_dir);
                
                // Clean up
                self::cleanup_directory($temp_dir);
                
                return [
                    'success' => true,
                    'page_id' => $page_id,
                    'theme_generated' => true,
                    'theme_slug' => $theme_slug
                ];
            }
            
            return [
                'success' => true,
                'page_id' => $page_id,
                'theme_generated' => false
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to create page'
        ];
    }
    
    private static function cleanup_directory($directory) {
        if (!file_exists($directory)) {
            return;
        }
        
        $files = glob($directory . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::cleanup_directory($file);
                rmdir($file);
            } else {
                unlink($file);
            }
        }
        
        rmdir($directory);
    }
}