<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Elementor {
    
    public static function is_elementor_active() {
        return defined('ELEMENTOR_VERSION') && class_exists('Elementor\Plugin');
    }
    
    public static function create_elementor_page($title, $content, $parent_page = 0) {
        if (!self::is_elementor_active()) {
            return false;
        }
        
        // Create the post
        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_parent'  => $parent_page
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Set Elementor meta data
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
        update_post_meta($post_id, '_elementor_template_type', 'wp-page');
        
        return $post_id;
    }
    
    public static function update_elementor_data($post_id, $elementor_data) {
        if (!self::is_elementor_active()) {
            return false;
        }
        
        update_post_meta($post_id, '_elementor_data', wp_slash(json_encode($elementor_data)));
        update_post_meta($post_id, '_elementor_css', '');
        
        return true;
    }
    
    public static function get_elementor_data($post_id) {
        if (!self::is_elementor_active()) {
            return false;
        }
        
        $elementor_data = get_post_meta($post_id, '_elementor_data', true);
        
        if ($elementor_data) {
            return json_decode($elementor_data, true);
        }
        
        return false;
    }
    
    public static function is_elementor_page($post_id) {
        if (!self::is_elementor_active()) {
            return false;
        }
        
        $edit_mode = get_post_meta($post_id, '_elementor_edit_mode', true);
        return $edit_mode === 'builder';
    }
    
    public static function rebuild_elementor_css($post_id) {
        if (!self::is_elementor_active()) {
            return false;
        }
        
        $elementor = Elementor\Plugin::$instance;
        $css_file = $elementor->css_manager->get_post_css_file($post_id);
        
        if ($css_file) {
            $css_file->update();
            return true;
        }
        
        return false;
    }
}