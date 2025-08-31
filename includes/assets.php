<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HTML_WP_Assets {
    public static $assets_url;
    public static function process($source_dir) {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/html-importer-assets/';
        self::$assets_url = $upload_dir['baseurl'] . '/html-importer-assets/';
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        // Copy assets (CSS, JS, Images)
        $files = glob($source_dir . '*.{css,js,png,jpg,jpeg,gif,svg}', GLOB_BRACE);
        foreach ($files as $file) {
            $filename = basename($file);
            copy($file, $target_dir . $filename);
        }
    }
    public static function rewrite_urls($html) {
        // Rewrite <img src="">
        $html = preg_replace(
            '/<img[^>]+src=["\']([^"\']+)["\']/i',
            '<img src="' . self::$assets_url . '$1"',
            $html
        );
        // Rewrite <link href="">
        $html = preg_replace(
            '/<link[^>]+href=["\']([^"\']+)["\']/i',
            '<link href="' . self::$assets_url . '$1"',
            $html
        );
        // Rewrite <script src="">
        $html = preg_replace(
            '/<script[^>]+src=["\']([^"\']+)["\']/i',
            '<script src="' . self::$assets_url . '$1"',
            $html
        );
        return $html;
    }
}