<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HTML_WP_Parser {
    private static $imported_pages = [];
    public static function process($file) {
        $file_type = wp_check_filetype($file['name']);
        $tmp_path  = $file['tmp_name'];
        if ($file_type['ext'] === 'zip') {
            self::process_zip($tmp_path);
        } elseif ($file_type['ext'] === 'html') {
            self::process_html(file_get_contents($tmp_path));
        } else {
            wp_die('Unsupported file type. Please upload .html or .zip');
        }
        // After processing all pages → build menu
        HTML_WP_Menu::build_menu(self::$imported_pages);
    }
    private static function process_zip($zip_path) {
        $extract_to = wp_upload_dir()['basedir'] . '/html-importer-tmp/';
        if (!file_exists($extract_to)) wp_mkdir_p($extract_to);
        $zip = new ZipArchive;
        if ($zip->open($zip_path) === TRUE) {
            $zip->extractTo($extract_to);
            $zip->close();
            // Process all HTML files
            $html_files = glob($extract_to . '*.html');
            foreach ($html_files as $html_file) {
                $content = file_get_contents($html_file);
                self::process_html($content);
            }
            // Process assets
            HTML_WP_Assets::process($extract_to);
        } else {
            wp_die('Could not unzip file.');
        }
    }
    private static function process_html($html) {
        // Extract title
        preg_match('/<title>(.*?)<\/title>/is', $html, $title_match);
        $title = !empty($title_match[1]) ? sanitize_text_field($title_match[1]) : 'Imported Page';
        // Extract body
        preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $body_match);
        $body_content = !empty($body_match[1]) ? $body_match[1] : $html;
        // Rewrite asset URLs
        $body_content = HTML_WP_Assets::rewrite_urls($body_content);
        // Insert as WordPress Page
        $page_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $body_content,
            'post_status'  => 'publish',
            'post_type'    => 'page'
        ]);
        self::$imported_pages[] = $page_id;
    }
}