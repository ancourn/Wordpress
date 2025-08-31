<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HTML_WP_Menu {
    public static function build_menu($page_ids) {
        // Create menu if not exists
        $menu_name   = 'Imported Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);
        if (!$menu_exists) {
            $menu_id = wp_create_nav_menu($menu_name);
            // Assign menu to primary location if theme supports it
            $locations = get_theme_mod('nav_menu_locations');
            if (isset($locations['primary'])) {
                $locations['primary'] = $menu_id;
                set_theme_mod('nav_menu_locations', $locations);
            }
        } else {
            $menu_id = $menu_exists->term_id;
        }
        // Add pages to menu
        foreach ($page_ids as $page_id) {
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title'   => get_the_title($page_id),
                'menu-item-object'  => 'page',
                'menu-item-object-id' => $page_id,
                'menu-item-type'    => 'post_type',
                'menu-item-status'  => 'publish'
            ));
        }
    }
}