<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Include required files for new menu system
require_once plugin_dir_path(__FILE__) . 'link-mapper.php';
require_once plugin_dir_path(__FILE__) . 'menu-builder.php';
require_once plugin_dir_path(__FILE__) . 'theme-generator.php';

class HTML_WP_Zip_Import {
    public static function import_zip($zip_path, $use_elementor = false, $parent_page = 0, $create_menu = true, $generate_theme = false) {
        $upload_dir = wp_upload_dir();
        $extract_to = $upload_dir['basedir'] . '/html_import_' . time();
        
        // Create extraction directory
        if (!file_exists($extract_to)) {
            mkdir($extract_to, 0755, true);
        }
        
        // Extract ZIP file
        $zip = new ZipArchive;
        if ($zip->open($zip_path) === TRUE) {
            $zip->extractTo($extract_to);
            $zip->close();
        } else {
            return new WP_Error('zip_error', 'Failed to open ZIP file');
        }
        
        // Find all HTML files (including subdirectories)
        $files = glob($extract_to . '/*.html');
        if (empty($files)) {
            // Look in subdirectories
            $files = glob($extract_to . '/**/*.html');
        }
        
        if (empty($files)) {
            // Clean up and return error
            self::cleanup_directory($extract_to);
            return new WP_Error('no_html_files', 'No HTML files found in the ZIP archive');
        }
        
        $results = [];
        $imported_pages = [];
        
        // Clear the link mapper at the start of processing
        HTML_WP_Link_Mapper::clear_map();
        
        foreach ($files as $file) {
            $html = file_get_contents($file);
            $filename = basename($file);
            
            // Extract title from HTML or use filename
            $page_title = self::extract_page_title($html, $file);
            
            // Handle assets
            require_once plugin_dir_path(__FILE__) . 'asset-handler.php';
            $html = HTML_WP_Asset_Handler::process_assets($extract_to, $html);
            
            // Create page
            if ($use_elementor && HTML_WP_Elementor::is_elementor_active()) {
                $post_id = self::create_elementor_page($page_title, $html, $parent_page);
            } else {
                $post_id = self::create_standard_page($page_title, $html, $parent_page);
            }
            
            if ($post_id && !is_wp_error($post_id)) {
                $results[] = [
                    'file' => $filename,
                    'page_id' => $post_id,
                    'title' => $page_title,
                    'edit_link' => get_edit_post_link($post_id),
                    'view_link' => get_permalink($post_id)
                ];
                $imported_pages[] = $post_id;
                
                // Add page to the link mapper
                HTML_WP_Link_Mapper::add_page($filename, $post_id);
            }
        }
        
        // Create navigation menu if requested using new system
        if ($create_menu && !empty($imported_pages)) {
            $pages_map = HTML_WP_Link_Mapper::get_page_map();
            HTML_WP_Menu_Builder::create_menu("Imported Site", $pages_map);
        }
        
        // Generate theme if requested
        $theme_slug = null;
        if ($generate_theme && !empty($imported_pages)) {
            $theme_slug = HTML_WP_Theme_Generator::generate_theme_from_pages($results, $extract_to);
        }
        
        // Clean up temporary directory
        self::cleanup_directory($extract_to);
        
        // Add theme information to results
        if ($theme_slug) {
            $results['theme_generated'] = true;
            $results['theme_slug'] = $theme_slug;
        } else {
            $results['theme_generated'] = false;
        }
        
        return $results;
    }
    
    private static function extract_page_title($html, $file_path) {
        // Try to extract title from HTML
        preg_match('/<title>(.*?)<\/title>/is', $html, $title_match);
        if (!empty($title_match[1])) {
            return sanitize_text_field($title_match[1]);
        }
        
        // Try to extract from h1 tag
        preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $h1_match);
        if (!empty($h1_match[1])) {
            return sanitize_text_field($h1_match[1]);
        }
        
        // Fallback to filename
        $filename = basename($file_path, '.html');
        return ucfirst(str_replace(['-', '_'], ' ', $filename));
    }
    
    private static function create_elementor_page($title, $html, $parent_page = 0) {
        require_once plugin_dir_path(__FILE__) . 'elementor-mapper.php';
        
        $elementor_data = HTML_WP_Elementor_Mapper::html_to_elementor_json($html);
        
        $post_data = [
            'post_title'  => $title,
            'post_status' => 'publish',
            'post_type'   => 'page',
            'post_parent' => $parent_page
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Set Elementor meta data
            update_post_meta($post_id, '_elementor_edit_mode', 'builder');
            update_post_meta($post_id, '_elementor_template_type', 'wp-page');
            
            if (defined('ELEMENTOR_VERSION')) {
                update_post_meta($post_id, '_elementor_version', ELEMENTOR_VERSION);
            }
            
            update_post_meta($post_id, '_elementor_data', wp_slash(json_encode($elementor_data)));
        }
        
        return $post_id;
    }
    
    private static function create_standard_page($title, $html, $parent_page = 0) {
        $post_data = [
            'post_title'   => $title,
            'post_content' => $html,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_parent'  => $parent_page
        ];
        
        return wp_insert_post($post_data);
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
    
    public static function get_zip_info($zip_path) {
        $zip = new ZipArchive;
        if ($zip->open($zip_path) !== TRUE) {
            return new WP_Error('zip_error', 'Failed to open ZIP file');
        }
        
        $html_files = [];
        $asset_files = [];
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if ($extension === 'html' || $extension === 'htm') {
                $html_files[] = $filename;
            } elseif (in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf'])) {
                $asset_files[] = $filename;
            }
        }
        
        $zip->close();
        
        return [
            'total_files' => $zip->numFiles,
            'html_files' => count($html_files),
            'asset_files' => count($asset_files),
            'html_file_list' => $html_files,
            'asset_file_list' => $asset_files
        ];
    }
}