<?php
/*
Plugin Name: HTML to WordPress Importer
Description: Import HTML/ZIP into WordPress pages/themes with Elementor mapping.
Version: 0.1
Author: Your Team
*/
if ( ! defined( 'ABSPATH' ) ) exit;
require_once __DIR__ . '/includes/parser.php';
require_once __DIR__ . '/includes/zip-import.php';
require_once __DIR__ . '/includes/asset-handler.php';
require_once __DIR__ . '/includes/elementor.php';
require_once __DIR__ . '/includes/elementor-mapper.php';
require_once __DIR__ . '/includes/elementor-auto-mapper.php';
require_once __DIR__ . '/includes/link-mapper.php';
require_once __DIR__ . '/includes/menu-builder.php';
require_once __DIR__ . '/includes/media-helper.php';
require_once __DIR__ . '/includes/theme-generator.php';
require_once __DIR__ . '/includes/queue.php';
add_action('admin_menu', function(){
    add_menu_page('HTML Importer', 'HTML Importer', 'manage_options', 'html-importer', function(){
        include __DIR__ . '/includes/admin-ui.php';
    }, 'dashicons-upload', 100);
});
add_action('admin_post_html_import_upload', function(){
    check_admin_referer('html_import_upload_nonce','html_import_upload_nonce_field');
    if (!empty($_FILES['html_zip'])) {
        $zip_tmp = $_FILES['html_zip']['tmp_name'];
        // Use Action Scheduler or direct import
        if (class_exists('ActionScheduler')) {
            ActionScheduler::enqueue_async_action('htwpi_import_job', array('zip_path' => $zip_tmp, 'user' => get_current_user_id()));
            wp_redirect(admin_url('admin.php?page=html-importer&queued=1'));
        } else {
            $res = HTML_WP_Zip_Import::import_zip($zip_tmp);
            wp_redirect(admin_url('admin.php?page=html-importer&result=done'));
        }
    } else {
        wp_redirect(admin_url('admin.php?page=html-importer&error=no_file'));
    }
    exit;
});