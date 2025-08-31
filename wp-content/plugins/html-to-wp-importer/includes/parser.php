<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Parser {
    private static $imported_pages = [];
    
    public static function process($file, $use_elementor = false) {
        $file_type = wp_check_filetype($file['name']);
        $tmp_path  = $file['tmp_name'];
        
        if ($file_type['ext'] === 'zip') {
            self::process_zip($tmp_path, $use_elementor);
        } elseif ($file_type['ext'] === 'html') {
            self::process_html(file_get_contents($tmp_path), $use_elementor);
        } else {
            wp_die('Unsupported file type. Please upload .html or .zip');
        }
        
        // After processing all pages → build menu
        if (!empty(self::$imported_pages)) {
            HTML_WP_Menu_Builder::create_menu('Imported Pages', self::$imported_pages);
        }
    }
    
    private static function process_zip($zip_path, $use_elementor = false) {
        $upload_dir = wp_upload_dir();
        $extract_to = $upload_dir['basedir'] . '/html-importer-tmp/';
        
        if (!file_exists($extract_to)) {
            wp_mkdir_p($extract_to);
        }
        
        $zip = new ZipArchive;
        if ($zip->open($zip_path) === TRUE) {
            $zip->extractTo($extract_to);
            $zip->close();
            
            // Process all HTML files
            $html_files = glob($extract_to . '*.html');
            foreach ($html_files as $html_file) {
                $content = file_get_contents($html_file);
                self::process_html($content, $use_elementor);
            }
            
            // Process assets
            HTML_WP_Asset_Handler::process($extract_to);
            
            // Clean up
            self::cleanup_directory($extract_to);
        } else {
            wp_die('Could not unzip file.');
        }
    }
    
    private static function process_html($html, $use_elementor = false) {
        // Extract title
        preg_match('/<title>(.*?)<\/title>/is', $html, $title_match);
        $title = !empty($title_match[1]) ? sanitize_text_field($title_match[1]) : 'Imported Page';
        
        // Extract body
        preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $body_match);
        $body_content = !empty($body_match[1]) ? $body_match[1] : $html;
        
        // Handle assets for single file imports
        if (strpos($body_content, 'src=') !== false || strpos($body_content, 'href=') !== false) {
            require_once __DIR__ . '/asset-handler.php';
            
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
            
            // Convert HTML to Elementor data
            if ($page_id && !is_wp_error($page_id)) {
                $elementor_data = HTML_WP_Elementor_AutoMapper::map_html_to_elementor($body_content);
                HTML_WP_Elementor::update_elementor_data($page_id, $elementor_data);
            }
        } else {
            $page_id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => $body_content,
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ]);
        }
        
        if ($page_id && !is_wp_error($page_id)) {
            self::$imported_pages[] = $page_id;
        }
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
    
    public static function get_imported_pages() {
        return self::$imported_pages;
    }
    
    public static function clear_imported_pages() {
        self::$imported_pages = [];
    }
}