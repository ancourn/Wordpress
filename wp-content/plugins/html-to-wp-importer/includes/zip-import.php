<?php
if ( ! defined( 'ABSPATH' ) ) exit;
require_once __DIR__ . '/asset-handler.php';
require_once __DIR__ . '/elementor-auto-mapper.php';
require_once __DIR__ . '/link-mapper.php';
require_once __DIR__ . '/menu-builder.php';
require_once __DIR__ . '/media-helper.php';
class HTML_WP_Zip_Import {
    public static function import_zip($zip_path) {
        $upload_dir = wp_upload_dir();
        $extract_to = $upload_dir['basedir'] . '/html_import_' . time();
        if (!file_exists($extract_to)) mkdir($extract_to,0755,true);
        $zip = new ZipArchive;
        if ($zip->open($zip_path) === TRUE) {
            $zip->extractTo($extract_to);
            $zip->close();
        } else {
            return new WP_Error('zip_error','Cannot open zip');
        }
        // copy assets to uploads and rewrite html
        HTML_WP_Asset_Handler::process($extract_to);
        $files = glob($extract_to . '/*.html');
        $page_map = [];
        $page_html_map = [];
        foreach ($files as $file) {
            $html = file_get_contents($file);
            // rewrite asset URLs inside html (handler already did per-file)
            $title = ucfirst(basename($file,'.html'));
            // map to elementor
            $sections = HTML_WP_Elementor_AutoMapper::map_html_to_elementor($html);
            // create post
            $post_id = wp_insert_post(['post_title'=>$title,'post_status'=>'publish','post_type'=>'page']);
            if ($post_id) {
                update_post_meta($post_id,'_elementor_edit_mode','builder');
                update_post_meta($post_id,'_elementor_data', wp_slash(json_encode($sections)));
                // sideload images in sections
                $sections = htwpi_replace_images_in_elementor_data($sections, $post_id);
                update_post_meta($post_id,'_elementor_data', wp_slash(json_encode($sections)));
                HTML_WP_Link_Mapper::register_page(basename($file), $post_id);
                $page_map[basename($file)] = $post_id;
                $page_html_map[$post_id] = $html;
            }
        }
        // rewrite internal links
        foreach ($page_map as $file => $pid) {
            $html = $page_html_map[$pid];
            $updated = HTML_WP_Link_Mapper::rewrite_links($html);
            // update post content fallback (in case raw html widgets exist)
            wp_update_post(['ID'=>$pid,'post_content'=>$updated]);
        }
        // generate menu
        HTML_WP_Menu_Builder::create_menu('Imported Site', array_values($page_map));
        return $page_map;
    }
}