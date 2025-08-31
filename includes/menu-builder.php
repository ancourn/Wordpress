<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HTML_WP_Menu_Builder {
    public static function create_menu($menu_name, $pages_map) {
        // Check if menu exists, else create
        $menu_exists = wp_get_nav_menu_object($menu_name);
        if (!$menu_exists) {
            $menu_id = wp_create_nav_menu($menu_name);
        } else {
            $menu_id = $menu_exists->term_id;
        }
        // Assign menu to primary theme location (if available)
        $locations = get_theme_mod('nav_menu_locations');
        if (isset($locations['primary'])) {
            $locations['primary'] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }
        // Add each page to the menu
        foreach ($pages_map as $filename => $page_id) {
            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title'  => get_the_title($page_id),
                'menu-item-object' => 'page',
                'menu-item-object-id' => $page_id,
                'menu-item-type'   => 'post_type',
                'menu-item-status' => 'publish'
            ]);
        }
        return $menu_id;
    }
}