<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Menu_Builder {
    /**
     * Create a navigation menu from a page map
     * 
     * @param string $menu_name The name of the menu to create
     * @param array $pages_map Array mapping filenames to page IDs
     * @return int|false The menu ID or false on failure
     */
    public static function create_menu($menu_name, $pages_map) {
        // Check if menu exists, else create
        $menu_exists = wp_get_nav_menu_object($menu_name);
        if (!$menu_exists) {
            $menu_id = wp_create_nav_menu($menu_name);
            if (is_wp_error($menu_id)) {
                return false;
            }
        } else {
            $menu_id = $menu_exists->term_id;
        }
        
        // Assign menu to primary theme location (if available)
        $locations = get_theme_mod('nav_menu_locations');
        if (isset($locations['primary'])) {
            $locations['primary'] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }
        
        // Clear existing menu items to avoid duplicates
        $existing_items = wp_get_nav_menu_items($menu_id);
        if ($existing_items) {
            foreach ($existing_items as $item) {
                wp_delete_post($item->ID, true);
            }
        }
        
        // Add each page to the menu
        foreach ($pages_map as $filename => $page_id) {
            $menu_item_data = [
                'menu-item-title'  => get_the_title($page_id),
                'menu-item-object' => 'page',
                'menu-item-object-id' => $page_id,
                'menu-item-type'   => 'post_type',
                'menu-item-status' => 'publish'
            ];
            
            wp_update_nav_menu_item($menu_id, 0, $menu_item_data);
        }
        
        return $menu_id;
    }
    
    /**
     * Create a menu with custom ordering
     * 
     * @param string $menu_name The name of the menu to create
     * @param array $pages_map Array mapping filenames to page IDs
     * @param array $order Array of filenames in desired order
     * @return int|false The menu ID or false on failure
     */
    public static function create_ordered_menu($menu_name, $pages_map, $order = []) {
        // If no custom order is provided, use the original order
        if (empty($order)) {
            return self::create_menu($menu_name, $pages_map);
        }
        
        // Create the menu
        $menu_id = self::create_menu($menu_name, $pages_map);
        if (!$menu_id) {
            return false;
        }
        
        // Reorder menu items based on the custom order
        $menu_items = wp_get_nav_menu_items($menu_id);
        if ($menu_items) {
            $position = 1;
            foreach ($order as $filename) {
                if (isset($pages_map[$filename])) {
                    $page_id = $pages_map[$filename];
                    foreach ($menu_items as $item) {
                        if ($item->object_id == $page_id) {
                            wp_update_post([
                                'ID' => $item->ID,
                                'menu_order' => $position++
                            ]);
                            break;
                        }
                    }
                }
            }
        }
        
        return $menu_id;
    }
    
    /**
     * Create a hierarchical menu based on file structure
     * 
     * @param string $menu_name The name of the menu to create
     * @param array $pages_map Array mapping filenames to page IDs
     * @param array $file_structure Array representing the file hierarchy
     * @return int|false The menu ID or false on failure
     */
    public static function create_hierarchical_menu($menu_name, $pages_map, $file_structure = []) {
        // If no file structure is provided, create a flat menu
        if (empty($file_structure)) {
            return self::create_menu($menu_name, $pages_map);
        }
        
        // Create the menu
        $menu_id = self::create_menu($menu_name, $pages_map);
        if (!$menu_id) {
            return false;
        }
        
        // Build hierarchical structure
        self::build_menu_hierarchy($menu_id, $pages_map, $file_structure);
        
        return $menu_id;
    }
    
    /**
     * Build menu hierarchy from file structure
     * 
     * @param int $menu_id The menu ID
     * @param array $pages_map Array mapping filenames to page IDs
     * @param array $structure The file structure
     * @param int $parent_item_id The parent menu item ID
     */
    private static function build_menu_hierarchy($menu_id, $pages_map, $structure, $parent_item_id = 0) {
        foreach ($structure as $item) {
            if (isset($item['filename']) && isset($pages_map[$item['filename']])) {
                $page_id = $pages_map[$item['filename']];
                
                $menu_item_data = [
                    'menu-item-title'  => get_the_title($page_id),
                    'menu-item-object' => 'page',
                    'menu-item-object-id' => $page_id,
                    'menu-item-type'   => 'post_type',
                    'menu-item-status' => 'publish',
                    'menu-item-parent-id' => $parent_item_id
                ];
                
                $result = wp_update_nav_menu_item($menu_id, 0, $menu_item_data);
                
                // If this item has children, process them recursively
                if (isset($item['children']) && !empty($item['children'])) {
                    self::build_menu_hierarchy($menu_id, $pages_map, $item['children'], $result);
                }
            }
        }
    }
    
    /**
     * Get menu by name
     * 
     * @param string $menu_name The menu name
     * @return object|false The menu object or false if not found
     */
    public static function get_menu($menu_name) {
        return wp_get_nav_menu_object($menu_name);
    }
    
    /**
     * Delete a menu by name
     * 
     * @param string $menu_name The menu name
     * @return bool True if deleted, false otherwise
     */
    public static function delete_menu($menu_name) {
        $menu = wp_get_nav_menu_object($menu_name);
        if ($menu) {
            return wp_delete_nav_menu($menu->term_id);
        }
        return false;
    }
    
    /**
     * Check if a menu exists
     * 
     * @param string $menu_name The menu name
     * @return bool True if exists, false otherwise
     */
    public static function menu_exists($menu_name) {
        return (bool) wp_get_nav_menu_object($menu_name);
    }
    
    /**
     * Get all menus created by this plugin
     * 
     * @return array Array of menu objects
     */
    public static function get_plugin_menus() {
        $menus = wp_get_nav_menus();
        $plugin_menus = [];
        
        foreach ($menus as $menu) {
            // Check if menu was created by our plugin (based on naming pattern)
            if (strpos($menu->name, 'Imported') !== false || strpos($menu->name, 'Site') !== false) {
                $plugin_menus[] = $menu;
            }
        }
        
        return $plugin_menus;
    }
}