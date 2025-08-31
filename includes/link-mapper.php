<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HTML_WP_Link_Mapper {
    private static $map = [];
    
    /**
     * Register imported page with its filename and permalink
     * 
     * @param string $filename The original HTML filename
     * @param int $page_id The WordPress page ID
     */
    public static function register_page($filename, $page_id) {
        $permalink = get_permalink($page_id);
        if ($permalink) {
            self::$map[$filename] = $permalink;
        }
    }
    
    /**
     * Register imported page with custom path mapping
     * 
     * @param string $original_path The original path from HTML
     * @param int $page_id The WordPress page ID
     */
    public static function register_path($original_path, $page_id) {
        $permalink = get_permalink($page_id);
        if ($permalink) {
            self::$map[$original_path] = $permalink;
        }
    }
    
    /**
     * Rewrite all <a href="..."> links in HTML content
     * 
     * @param string $html The HTML content to process
     * @return string The HTML with rewritten links
     */
    public static function rewrite_links($html) {
        // Rewrite standard <a href> links
        $html = preg_replace_callback(
            '/<a\s+[^>]*href=["\']([^"\']+)["\']/i',
            function($matches) {
                return self::rewrite_single_link($matches[0], $matches[1]);
            },
            $html
        );
        
        // Rewrite form action URLs
        $html = preg_replace_callback(
            '/<form\s+[^>]*action=["\']([^"\']+)["\']/i',
            function($matches) {
                return self::rewrite_single_link($matches[0], $matches[1]);
            },
            $html
        );
        
        // Rewrite link href attributes
        $html = preg_replace_callback(
            '/<link\s+[^>]*href=["\']([^"\']+)["\']/i',
            function($matches) {
                return self::rewrite_single_link($matches[0], $matches[1]);
            },
            $html
        );
        
        // Rewrite script src attributes
        $html = preg_replace_callback(
            '/<script\s+[^>]*src=["\']([^"\']+)["\']/i',
            function($matches) {
                return self::rewrite_single_link($matches[0], $matches[1]);
            },
            $html
        );
        
        return $html;
    }
    
    /**
     * Rewrite a single link based on registered mappings
     * 
     * @param string $original_match The original HTML attribute match
     * @param string $url The URL to rewrite
     * @return string The rewritten HTML attribute
     */
    private static function rewrite_single_link($original_match, $url) {
        // Skip external URLs and anchor links
        if (self::is_external_url($url) || self::is_anchor_link($url) || self::is_mailto_link($url) || self::is_tel_link($url)) {
            return $original_match;
        }
        
        // Skip data URLs and JavaScript URLs
        if (self::is_data_url($url) || self::is_javascript_url($url)) {
            return $original_match;
        }
        
        // Try to find matching file in the map
        $filename = basename($url);
        if (isset(self::$map[$filename])) {
            return str_replace($url, self::$map[$filename], $original_match);
        }
        
        // Try to find matching path in the map
        $path = self::normalize_path($url);
        if (isset(self::$map[$path])) {
            return str_replace($url, self::$map[$path], $original_match);
        }
        
        // Try to find partial path matches
        foreach (self::$map as $key => $permalink) {
            if (strpos($url, $key) !== false) {
                return str_replace($key, $permalink, $original_match);
            }
        }
        
        // Return original if no match found
        return $original_match;
    }
    
    /**
     * Check if URL is external
     * 
     * @param string $url The URL to check
     * @return bool True if external
     */
    private static function is_external_url($url) {
        return preg_match('#^(https?:)?//#i', $url) || 
               preg_match('#^[a-z]+://#i', $url) ||
               preg_match('#^//#i', $url);
    }
    
    /**
     * Check if URL is an anchor link
     * 
     * @param string $url The URL to check
     * @return bool True if anchor link
     */
    private static function is_anchor_link($url) {
        return strpos($url, '#') === 0;
    }
    
    /**
     * Check if URL is a mailto link
     * 
     * @param string $url The URL to check
     * @return bool True if mailto link
     */
    private static function is_mailto_link($url) {
        return strpos($url, 'mailto:') === 0;
    }
    
    /**
     * Check if URL is a tel link
     * 
     * @param string $url The URL to check
     * @return bool True if tel link
     */
    private static function is_tel_link($url) {
        return strpos($url, 'tel:') === 0;
    }
    
    /**
     * Check if URL is a data URL
     * 
     * @param string $url The URL to check
     * @return bool True if data URL
     */
    private static function is_data_url($url) {
        return strpos($url, 'data:') === 0;
    }
    
    /**
     * Check if URL is a JavaScript URL
     * 
     * @param string $url The URL to check
     * @return bool True if JavaScript URL
     */
    private static function is_javascript_url($url) {
        return strpos($url, 'javascript:') === 0;
    }
    
    /**
     * Normalize path for comparison
     * 
     * @param string $path The path to normalize
     * @return string Normalized path
     */
    private static function normalize_path($path) {
        // Remove query string and fragment
        $path = preg_replace('/[?#].*$/', '', $path);
        
        // Remove leading/trailing slashes
        $path = trim($path, '/');
        
        return $path;
    }
    
    /**
     * Get the current link mapping
     * 
     * @return array The current mapping
     */
    public static function get_mapping() {
        return self::$map;
    }
    
    /**
     * Clear the link mapping
     */
    public static function clear_mapping() {
        self::$map = [];
    }
    
    /**
     * Get statistics about the link mapping
     * 
     * @return array Statistics about the mapping
     */
    public static function get_statistics() {
        return [
            'total_mapped' => count(self::$map),
            'mapped_files' => array_keys(self::$map),
            'mapping_details' => self::$map
        ];
    }
}