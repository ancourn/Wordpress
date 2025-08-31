<?php
/*
Plugin Name: HTML to WordPress Importer
Plugin URI: https://yourdomain.com/
Description: Import static HTML/ZIP websites into WordPress as pages with assets.
Version: 0.1
Author: Your Team
License: GPL2
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include required files
require_once plugin_dir_path( __FILE__ ) . 'includes/parser.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/assets.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/elementor.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/zip-import.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/asset-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-ui.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/link-mapper.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/menu-builder.php';

// Enhanced functionality files
require_once plugin_dir_path( __FILE__ ) . 'includes/media-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/elementor-image-replacer.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/html-image-replacer.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/form-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/cf7-integration.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/background-processor.php';

class HTML_To_WP_Importer {
    public function __construct() {
        // Create upload directory on plugin activation
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Register shortcodes and hooks
        add_action('init', array($this, 'register_shortcodes'));
        add_action('init', array($this, 'register_hooks'));
    }
    
    public function activate() {
        $upload_dir = wp_upload_dir();
        $asset_dir = $upload_dir['basedir'] . '/html-importer';
        
        if (!file_exists($asset_dir)) {
            wp_mkdir_p($asset_dir);
        }
    }
    
    public function register_shortcodes() {
        HTML_WP_Form_Handler::register_shortcodes();
    }
    
    public function register_hooks() {
        HTML_WP_Background_Processor::register_hooks();
    }
}

new HTML_To_WP_Importer();