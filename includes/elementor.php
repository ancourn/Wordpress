<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HTML_WP_Elementor {
    public static function convert_html_to_elementor($html_content) {
        // Include the Elementor mapper
        require_once plugin_dir_path(__FILE__) . 'elementor-mapper.php';
        
        // Convert HTML to Elementor JSON structure
        $elementor_data = HTML_WP_Elementor_Mapper::html_to_elementor_json($html_content);
        
        return $elementor_data;
    }
    
    public static function create_elementor_page($title, $html_content, $parent_page = 0) {
        // Convert HTML to Elementor format
        $elementor_data = self::convert_html_to_elementor($html_content);
        
        // Create the page with Elementor data
        $page_data = array(
            'post_title'   => $title,
            'post_content' => '', // Empty content for Elementor
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_parent'  => $parent_page,
        );
        
        $page_id = wp_insert_post($page_data);
        
        if (is_wp_error($page_id)) {
            throw new Exception('Failed to create page: ' . $page_id->get_error_message());
        }
        
        // Save Elementor data
        update_post_meta($page_id, '_elementor_data', $elementor_data);
        update_post_meta($page_id, '_elementor_edit_mode', 'builder');
        update_post_meta($page_id, '_elementor_template_type', 'wp-page');
        
        return $page_id;
    }
    
    private static function html_to_elementor_json($html) {
        return HTML_WP_Elementor_Mapper::html_to_elementor_json($html);
    }
    
    public static function is_elementor_active() {
        return defined('ELEMENTOR_VERSION') || class_exists('Elementor\Plugin');
    }
    
    public static function get_supported_elements() {
        return [
            'heading' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
            'text-editor' => ['p', 'div', 'span'],
            'image' => ['img'],
            'button' => ['a'],
            'html' => ['ul', 'ol', 'table', 'div', 'span']
        ];
    }
}