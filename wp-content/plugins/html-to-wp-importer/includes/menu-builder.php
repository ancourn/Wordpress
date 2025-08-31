<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Menu_Builder {
    
    public static function create_menu($menu_name, $page_ids) {
        if (empty($page_ids)) {
            return false;
        }
        
        // Create the menu
        $menu_id = wp_create_nav_menu($menu_name);
        
        if (is_wp_error($menu_id)) {
            return $menu_id;
        }
        
        // Add pages to the menu
        foreach ($page_ids as $page_id) {
            $menu_item_data = [
                'menu-item-object-id' => $page_id,
                'menu-item-object' => 'page',
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish'
            ];
            
            wp_update_nav_menu_item($menu_id, 0, $menu_item_data);
        }
        
        // Assign menu to theme location
        self::assign_menu_to_location($menu_id);
        
        return $menu_id;
    }
    
    public static function create_menu_from_structure($menu_name, $menu_structure) {
        if (empty($menu_structure)) {
            return false;
        }
        
        // Create the menu
        $menu_id = wp_create_nav_menu($menu_name);
        
        if (is_wp_error($menu_id)) {
            return $menu_id;
        }
        
        // Process menu structure
        self::process_menu_structure($menu_id, $menu_structure, 0);
        
        // Assign menu to theme location
        self::assign_menu_to_location($menu_id);
        
        return $menu_id;
    }
    
    private static function process_menu_structure($menu_id, $items, $parent_id = 0) {
        foreach ($items as $item) {
            $menu_item_data = [
                'menu-item-title' => $item['title'],
                'menu-item-url' => $item['url'] ?? '#',
                'menu-item-status' => 'publish',
                'menu-item-parent-id' => $parent_id
            ];
            
            // Handle different item types
            if (isset($item['type'])) {
                switch ($item['type']) {
                    case 'page':
                        if (isset($item['page_id'])) {
                            $menu_item_data['menu-item-object-id'] = $item['page_id'];
                            $menu_item_data['menu-item-object'] = 'page';
                            $menu_item_data['menu-item-type'] = 'post_type';
                        }
                        break;
                    case 'category':
                        if (isset($item['category_id'])) {
                            $menu_item_data['menu-item-object-id'] = $item['category_id'];
                            $menu_item_data['menu-item-object'] = 'category';
                            $menu_item_data['menu-item-type'] = 'taxonomy';
                        }
                        break;
                    case 'custom':
                    default:
                        $menu_item_data['menu-item-type'] = 'custom';
                        break;
                }
            }
            
            // Add menu item
            $menu_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);
            
            // Add custom attributes if provided
            if (isset($item['attr_title'])) {
                update_post_meta($menu_item_id, '_menu_item_attr_title', $item['attr_title']);
            }
            
            if (isset($item['target'])) {
                update_post_meta($menu_item_id, '_menu_item_target', $item['target']);
            }
            
            if (isset($item['classes'])) {
                update_post_meta($menu_item_id, '_menu_item_classes', $item['classes']);
            }
            
            if (isset($item['xfn'])) {
                update_post_meta($menu_item_id, '_menu_item_xfn', $item['xfn']);
            }
            
            if (isset($item['description'])) {
                update_post_meta($menu_item_id, '_menu_item_description', $item['description']);
            }
            
            // Process children
            if (isset($item['children']) && !empty($item['children'])) {
                self::process_menu_structure($menu_id, $item['children'], $menu_item_id);
            }
        }
    }
    
    private static function assign_menu_to_location($menu_id) {
        // Get available theme locations
        $locations = get_registered_nav_menus();
        
        if (empty($locations)) {
            return false;
        }
        
        // Try to assign to primary location first
        $primary_locations = ['primary', 'main', 'primary_menu', 'main_menu'];
        
        foreach ($primary_locations as $location) {
            if (isset($locations[$location])) {
                $theme_mods = get_theme_mod('nav_menu_locations');
                $theme_mods[$location] = $menu_id;
                set_theme_mod('nav_menu_locations', $theme_mods);
                return true;
            }
        }
        
        // If no primary location found, assign to first available location
        $first_location = array_key_first($locations);
        if ($first_location) {
            $theme_mods = get_theme_mod('nav_menu_locations');
            $theme_mods[$first_location] = $menu_id;
            set_theme_mod('nav_menu_locations', $theme_mods);
            return true;
        }
        
        return false;
    }
    
    public static function get_menu_by_name($menu_name) {
        $menu = wp_get_nav_menu_object($menu_name);
        return $menu ? $menu : false;
    }
    
    public static function delete_menu($menu_name) {
        $menu = self::get_menu_by_name($menu_name);
        if ($menu) {
            wp_delete_nav_menu($menu->term_id);
            return true;
        }
        return false;
    }
    
    public static function get_menu_items($menu_id) {
        $items = wp_get_nav_menu_items($menu_id);
        return $items ? $items : [];
    }
    
    public static function build_menu_from_html($html_content) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $menu_structure = [];
        $nav_elements = $dom->getElementsByTagName('nav');
        
        foreach ($nav_elements as $nav) {
            $ul_elements = $nav->getElementsByTagName('ul');
            foreach ($ul_elements as $ul) {
                $menu_structure = array_merge($menu_structure, self::process_ul_element($ul));
            }
        }
        
        // Also check for standalone ul elements
        $ul_elements = $dom->getElementsByTagName('ul');
        foreach ($ul_elements as $ul) {
            if ($ul->parentNode->tagName !== 'nav') {
                $menu_structure = array_merge($menu_structure, self::process_ul_element($ul));
            }
        }
        
        return $menu_structure;
    }
    
    private static function process_ul_element($ul) {
        $items = [];
        $li_elements = $ul->getElementsByTagName('li');
        
        foreach ($li_elements as $li) {
            $item = [
                'title' => $li->textContent,
                'url' => '#',
                'children' => []
            ];
            
            // Find link element
            $link = $li->getElementsByTagName('a')->item(0);
            if ($link) {
                $item['title'] = $link->textContent;
                $item['url'] = $link->getAttribute('href');
                $item['type'] = 'custom';
                
                // Check if it's an internal link
                if (!self::is_external_url($item['url'])) {
                    $page_id = url_to_postid($item['url']);
                    if ($page_id) {
                        $item['type'] = 'page';
                        $item['page_id'] = $page_id;
                    }
                }
            }
            
            // Process sub-menu
            $sub_ul = $li->getElementsByTagName('ul')->item(0);
            if ($sub_ul) {
                $item['children'] = self::process_ul_element($sub_ul);
            }
            
            $items[] = $item;
        }
        
        return $items;
    }
    
    private static function is_external_url($url) {
        return preg_match('/^[a-zA-Z]+:\/\//', $url) && strpos($url, home_url()) === false;
    }
}