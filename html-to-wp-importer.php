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

class HTML_To_WP_Importer {
    public function __construct() {
        // Create upload directory on plugin activation
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function activate() {
        $upload_dir = wp_upload_dir();
        $asset_dir = $upload_dir['basedir'] . '/html-importer';
        
        if (!file_exists($asset_dir)) {
            wp_mkdir_p($asset_dir);
        }
    }
}

new HTML_To_WP_Importer();