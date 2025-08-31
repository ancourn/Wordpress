<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Link_Mapper {
    private static $page_map = [];
    
    public static function register_page($filename, $post_id) {
        self::$page_map[$filename] = $post_id;
    }
    
    public static function rewrite_links($html) {
        // Rewrite href attributes in anchor tags
        $html = preg_replace_callback(
            '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i',
            function($matches) {
                return self::rewrite_single_link($matches[0], $matches[1]);
            },
            $html
        );
        
        // Rewrite src attributes in iframes
        $html = preg_replace_callback(
            '/<iframe[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
            function($matches) {
                return self::rewrite_single_link($matches[0], $matches[1], 'src');
            },
            $html
        );
        
        return $html;
    }
    
    private static function rewrite_single_link($tag, $original_url, $attribute = 'href') {
        // Skip external URLs
        if (self::is_external_url($original_url)) {
            return $tag;
        }
        
        // Skip anchor links
        if (strpos($original_url, '#') === 0) {
            return $tag;
        }
        
        // Skip mailto links
        if (strpos($original_url, 'mailto:') === 0) {
            return $tag;
        }
        
        // Skip tel links
        if (strpos($original_url, 'tel:') === 0) {
            return $tag;
        }
        
        // Skip javascript links
        if (strpos($original_url, 'javascript:') === 0) {
            return $tag;
        }
        
        // Clean the URL
        $clean_url = self::clean_url($original_url);
        
        // Try to find a matching page
        $new_url = self::find_matching_page($clean_url);
        
        if ($new_url) {
            return str_replace($attribute . '="' . $original_url . '"', $attribute . '="' . $new_url . '"', $tag);
        }
        
        return $tag;
    }
    
    private static function is_external_url($url) {
        // Check if URL has a scheme
        if (preg_match('/^[a-zA-Z]+:\/\//', $url)) {
            // Parse the URL
            $parsed_url = parse_url($url);
            
            // Get current site URL
            $site_url = get_site_url();
            $parsed_site_url = parse_url($site_url);
            
            // Compare hosts
            if (isset($parsed_url['host']) && isset($parsed_site_url['host'])) {
                return $parsed_url['host'] !== $parsed_site_url['host'];
            }
            
            return true;
        }
        
        return false;
    }
    
    private static function clean_url($url) {
        // Remove query parameters
        $url_parts = parse_url($url);
        $clean_url = $url_parts['path'] ?? $url;
        
        // Remove leading slash
        $clean_url = ltrim($clean_url, '/');
        
        // Remove index.html or index.htm
        $clean_url = preg_replace('/index\.(html|htm)$/i', '', $clean_url);
        
        // Remove .html or .htm extension
        $clean_url = preg_replace('/\.(html|htm)$/i', '', $clean_url);
        
        return $clean_url;
    }
    
    private static function find_matching_page($clean_url) {
        // Try exact match with filename
        foreach (self::$page_map as $filename => $post_id) {
            $filename_clean = preg_replace('/\.(html|htm)$/i', '', $filename);
            if ($filename_clean === $clean_url) {
                return get_permalink($post_id);
            }
        }
        
        // Try basename match
        $basename = basename($clean_url);
        foreach (self::$page_map as $filename => $post_id) {
            $filename_clean = preg_replace('/\.(html|htm)$/i', '', basename($filename));
            if ($filename_clean === $basename) {
                return get_permalink($post_id);
            }
        }
        
        // Try partial match
        foreach (self::$page_map as $filename => $post_id) {
            $filename_clean = preg_replace('/\.(html|htm)$/i', '', $filename);
            if (strpos($filename_clean, $clean_url) !== false || strpos($clean_url, $filename_clean) !== false) {
                return get_permalink($post_id);
            }
        }
        
        // Try to find page by title
        $page = get_page_by_title($clean_url, OBJECT, 'page');
        if ($page) {
            return get_permalink($page->ID);
        }
        
        // Try to find page by slug
        $page = get_page_by_path($clean_url, OBJECT, 'page');
        if ($page) {
            return get_permalink($page->ID);
        }
        
        return null;
    }
    
    public static function get_page_map() {
        return self::$page_map;
    }
    
    public static function clear_page_map() {
        self::$page_map = [];
    }
    
    public static function export_page_map() {
        return json_encode(self::$page_map);
    }
    
    public static function import_page_map($json_data) {
        $data = json_decode($json_data, true);
        if (is_array($data)) {
            self::$page_map = $data;
            return true;
        }
        return false;
    }
}