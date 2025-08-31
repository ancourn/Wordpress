<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HTML_WP_Link_Mapper {
    private static $page_map = [];
    
    /**
     * Add a page to the mapping
     * 
     * @param string $filename The original HTML filename
     * @param int $page_id The WordPress page ID
     */
    public static function add_page($filename, $page_id) {
        self::$page_map[$filename] = $page_id;
    }
    
    /**
     * Get the page map (filename => page_id)
     * 
     * @return array The page mapping
     */
    public static function get_page_map() {
        return self::$page_map;
    }
    
    /**
     * Get a page ID by filename
     * 
     * @param string $filename The HTML filename
     * @return int|false The page ID or false if not found
     */
    public static function get_page_id($filename) {
        return isset(self::$page_map[$filename]) ? self::$page_map[$filename] : false;
    }
    
    /**
     * Clear the page map
     */
    public static function clear_map() {
        self::$page_map = [];
    }
    
    /**
     * Get the count of mapped pages
     * 
     * @return int The number of mapped pages
     */
    public static function get_count() {
        return count(self::$page_map);
    }
}