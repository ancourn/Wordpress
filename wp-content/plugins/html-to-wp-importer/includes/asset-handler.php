<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Asset_Handler {
    private static $asset_map = [];
    private static $upload_base_url;
    private static $upload_base_path;
    
    public static function process($extract_dir) {
        self::init_upload_dirs();
        
        // Find all asset files
        $asset_files = self::find_asset_files($extract_dir);
        
        // Copy assets to WordPress uploads directory
        $copied_assets = self::copy_assets($asset_files, $extract_dir);
        
        // Rewrite URLs in HTML files
        self::rewrite_html_files($extract_dir, $copied_assets);
        
        return $copied_assets;
    }
    
    public static function process_assets($extract_dir, $html) {
        self::init_upload_dirs();
        
        // Find all asset files
        $asset_files = self::find_asset_files($extract_dir);
        
        // Copy assets to WordPress uploads directory
        $copied_assets = self::copy_assets($asset_files, $extract_dir);
        
        // Rewrite URLs in HTML content
        $rewritten_html = self::rewrite_asset_urls($html, self::$upload_base_url, $copied_assets);
        
        return $rewritten_html;
    }
    
    private static function init_upload_dirs() {
        $upload_dir = wp_upload_dir();
        self::$upload_base_url = $upload_dir['baseurl'] . '/html-importer-assets';
        self::$upload_base_path = $upload_dir['basedir'] . '/html-importer-assets';
        
        if (!file_exists(self::$upload_base_path)) {
            wp_mkdir_p(self::$upload_base_path);
        }
    }
    
    private static function find_asset_files($directory) {
        $assets = [];
        $supported_extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'otf', 'eot', 'ico', 'webp'];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));
                if (in_array($extension, $supported_extensions)) {
                    $relative_path = substr($file->getPathname(), strlen($directory) + 1);
                    $assets[$relative_path] = $file->getPathname();
                }
            }
        }
        
        return $assets;
    }
    
    private static function copy_assets($asset_files, $extract_dir) {
        $copied_assets = [];
        
        foreach ($asset_files as $relative_path => $source_path) {
            $destination_path = self::$upload_base_path . '/' . $relative_path;
            $destination_dir = dirname($destination_path);
            
            if (!file_exists($destination_dir)) {
                wp_mkdir_p($destination_dir);
            }
            
            if (copy($source_path, $destination_path)) {
                $url = self::$upload_base_url . '/' . $relative_path;
                $copied_assets[$relative_path] = [
                    'source' => $source_path,
                    'destination' => $destination_path,
                    'url' => $url,
                    'size' => filesize($source_path)
                ];
                
                // Store mapping for URL rewriting
                self::$asset_map[$relative_path] = $url;
            }
        }
        
        return $copied_assets;
    }
    
    private static function rewrite_html_files($extract_dir, $copied_assets) {
        $html_files = glob($extract_dir . '/*.html');
        
        foreach ($html_files as $html_file) {
            $html_content = file_get_contents($html_file);
            $rewritten_content = self::rewrite_asset_urls($html_content, self::$upload_base_url, $copied_assets);
            file_put_contents($html_file, $rewritten_content);
        }
    }
    
    public static function rewrite_asset_urls($html, $assets_url, $copied_assets) {
        // Rewrite img src attributes
        $html = preg_replace_callback(
            '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
            function($matches) use ($copied_assets) {
                return self::rewrite_single_url($matches[0], $matches[1], $copied_assets, 'src');
            },
            $html
        );
        
        // Rewrite link href attributes (CSS)
        $html = preg_replace_callback(
            '/<link[^>]+href=["\']([^"\']+)["\'][^>]*>/i',
            function($matches) use ($copied_assets) {
                return self::rewrite_single_url($matches[0], $matches[1], $copied_assets, 'href');
            },
            $html
        );
        
        // Rewrite script src attributes
        $html = preg_replace_callback(
            '/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
            function($matches) use ($copied_assets) {
                return self::rewrite_single_url($matches[0], $matches[1], $copied_assets, 'src');
            },
            $html
        );
        
        // Rewrite background images in inline styles
        $html = preg_replace_callback(
            '/background-image:\s*url\(["\']([^"\']+)["\']\)/i',
            function($matches) use ($copied_assets) {
                $original_url = $matches[1];
                $new_url = self::find_matching_asset($original_url, $copied_assets);
                if ($new_url) {
                    return 'background-image: url("' . $new_url . '")';
                }
                return $matches[0];
            },
            $html
        );
        
        // Rewrite content URLs in CSS url() functions
        $html = preg_replace_callback(
            '/url\(["\']([^"\']+)["\']\)/i',
            function($matches) use ($copied_assets) {
                $original_url = $matches[1];
                $new_url = self::find_matching_asset($original_url, $copied_assets);
                if ($new_url) {
                    return 'url("' . $new_url . '")';
                }
                return $matches[0];
            },
            $html
        );
        
        return $html;
    }
    
    private static function rewrite_single_url($tag, $original_url, $copied_assets, $attribute) {
        $new_url = self::find_matching_asset($original_url, $copied_assets);
        if ($new_url) {
            return str_replace($attribute . '="' . $original_url . '"', $attribute . '="' . $new_url . '"', $tag);
        }
        return $tag;
    }
    
    private static function find_matching_asset($original_url, $copied_assets) {
        // Remove query parameters
        $url_parts = parse_url($original_url);
        $clean_url = $url_parts['path'] ?? $original_url;
        
        // Remove leading slash
        $clean_url = ltrim($clean_url, '/');
        
        // Check for exact match first
        if (isset($copied_assets[$clean_url])) {
            return $copied_assets[$clean_url]['url'];
        }
        
        // Check for filename match (without path)
        $filename = basename($clean_url);
        foreach ($copied_assets as $relative_path => $asset_info) {
            if (basename($relative_path) === $filename) {
                return $asset_info['url'];
            }
        }
        
        // Check for partial path match
        foreach ($copied_assets as $relative_path => $asset_info) {
            if (strpos($relative_path, $filename) !== false) {
                return $asset_info['url'];
            }
        }
        
        return null;
    }
    
    public static function get_asset_info($extract_dir) {
        $asset_files = self::find_asset_files($extract_dir);
        $info = [
            'total_files' => count($asset_files),
            'total_size' => 0,
            'by_type' => []
        ];
        
        foreach ($asset_files as $relative_path => $source_path) {
            $size = filesize($source_path);
            $info['total_size'] += $size;
            
            $extension = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
            if (!isset($info['by_type'][$extension])) {
                $info['by_type'][$extension] = [
                    'count' => 0,
                    'size' => 0
                ];
            }
            $info['by_type'][$extension]['count']++;
            $info['by_type'][$extension]['size'] += $size;
        }
        
        return $info;
    }
    
    public static function cleanup_old_assets($days_to_keep = 30) {
        if (!file_exists(self::$upload_base_path)) {
            return;
        }
        
        $cutoff_time = time() - ($days_to_keep * 24 * 60 * 60);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::$upload_base_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->getMTime() < $cutoff_time) {
                if ($file->isDir()) {
                    @rmdir($file->getPathname());
                } else {
                    @unlink($file->getPathname());
                }
            }
        }
    }
}