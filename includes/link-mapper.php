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
    public static function add_page_mapping($filename, $page_id) {
        self::$page_map[$filename] = $page_id;
    }
    
    /**
     * Get the complete page map (filename => page_id)
     * 
     * @return array The page mapping
     */
    public static function get_page_map() {
        return self::$page_map;
    }
    
    /**
     * Get page ID by filename
     * 
     * @param string $filename The original HTML filename
     * @return int|null The WordPress page ID or null if not found
     */
    public static function get_page_id($filename) {
        return isset(self::$page_map[$filename]) ? self::$page_map[$filename] : null;
    }
    
    /**
     * Clear the page map
     */
    public static function clear_page_map() {
        self::$page_map = [];
    }
    
    /**
     * Build page map from import results
     * 
     * @param array $results Import results from ZIP import
     * @return array The page mapping
     */
    public static function build_page_map_from_results($results) {
        self::clear_page_map();
        
        foreach ($results as $result) {
            if (isset($result['file']) && isset($result['page_id'])) {
                self::add_page_mapping($result['file'], $result['page_id']);
            }
        }
        
        return self::$page_map;
    }
}