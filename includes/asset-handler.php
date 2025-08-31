<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HTML_WP_Asset_Handler {
    public static function process_assets($extract_dir, $html) {
        $upload_dir = wp_upload_dir();
        $assets_url = $upload_dir['baseurl'] . '/html_import_assets';
        $assets_path = $upload_dir['basedir'] . '/html_import_assets';
        
        // Create assets directory if it doesn't exist
        if (!file_exists($assets_path)) {
            mkdir($assets_path, 0755, true);
        }
        
        // Copy assets (css, js, images, fonts) into WP uploads
        $asset_extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'otf', 'eot'];
        $copied_assets = [];
        
        foreach ($asset_extensions as $ext) {
            // Check for files in root directory
            $files = glob("$extract_dir/*.$ext");
            foreach ($files as $file) {
                $filename = basename($file);
                $destination = "$assets_path/$filename";
                if (copy($file, $destination)) {
                    $copied_assets[] = $filename;
                }
            }
            
            // Check for files in subdirectories
            $subdir_files = glob("$extract_dir/**/*.$ext");
            foreach ($subdir_files as $file) {
                $filename = basename($file);
                $destination = "$assets_path/$filename";
                
                // Handle duplicate filenames by adding a prefix
                if (file_exists($destination)) {
                    $prefix = dirname(str_replace($extract_dir, '', $file));
                    $prefix = trim($prefix, '/');
                    if (!empty($prefix)) {
                        $filename = $prefix . '_' . $filename;
                        $destination = "$assets_path/$filename";
                    }
                }
                
                if (copy($file, $destination)) {
                    $copied_assets[] = $filename;
                }
            }
        }
        
        // Rewrite URLs in HTML
        $html = self::rewrite_asset_urls($html, $assets_url, $copied_assets);
        
        return $html;
    }
    
    private static function rewrite_asset_urls($html, $assets_url, $copied_assets) {
        // Rewrite src attributes
        $html = preg_replace_callback(
            '/src=["\']([^"\']+)["\']/i',
            function ($matches) use ($assets_url, $copied_assets) {
                return self::rewrite_single_url($matches[0], $matches[1], $assets_url, $copied_assets);
            },
            $html
        );
        
        // Rewrite href attributes (for CSS, JS, etc.)
        $html = preg_replace_callback(
            '/href=["\']([^"\']+)["\']/i',
            function ($matches) use ($assets_url, $copied_assets) {
                return self::rewrite_single_url($matches[0], $matches[1], $assets_url, $copied_assets);
            },
            $html
        );
        
        // Rewrite background-image URLs in CSS
        $html = preg_replace_callback(
            '/background-image:\s*url\(["\']?([^"\')]+)["\']?\)/i',
            function ($matches) use ($assets_url, $copied_assets) {
                return self::rewrite_single_url($matches[0], $matches[1], $assets_url, $copied_assets, 'background-image');
            },
            $html
        );
        
        // Rewrite content: URLs in CSS
        $html = preg_replace_callback(
            '/content:\s*url\(["\']?([^"\')]+)["\']?\)/i',
            function ($matches) use ($assets_url, $copied_assets) {
                return self::rewrite_single_url($matches[0], $matches[1], $assets_url, $copied_assets, 'content');
            },
            $html
        );
        
        return $html;
    }
    
    private static function rewrite_single_url($original_match, $url, $assets_url, $copied_assets, $type = 'src') {
        // Skip external URLs and data URLs
        if (self::is_external_url($url) || self::is_data_url($url)) {
            return $original_match;
        }
        
        // Get the filename from the URL
        $filename = basename($url);
        
        // Check if the asset was copied
        if (in_array($filename, $copied_assets)) {
            $new_url = $assets_url . '/' . $filename;
            
            if ($type === 'src' || $type === 'href') {
                return $type . '="' . $new_url . '"';
            } elseif ($type === 'background-image') {
                return 'background-image: url("' . $new_url . '")';
            } elseif ($type === 'content') {
                return 'content: url("' . $new_url . '")';
            }
        }
        
        // Check for prefixed filenames (from subdirectories)
        foreach ($copied_assets as $copied_asset) {
            if (strpos($copied_asset, '_') !== false) {
                $parts = explode('_', $copied_asset, 2);
                if (count($parts) === 2 && $parts[1] === $filename) {
                    $new_url = $assets_url . '/' . $copied_asset;
                    
                    if ($type === 'src' || $type === 'href') {
                        return $type . '="' . $new_url . '"';
                    } elseif ($type === 'background-image') {
                        return 'background-image: url("' . $new_url . '")';
                    } elseif ($type === 'content') {
                        return 'content: url("' . $new_url . '")';
                    }
                }
            }
        }
        
        // Return original if no match found
        return $original_match;
    }
    
    private static function is_external_url($url) {
        return preg_match('#^(https?:)?//#i', $url) || 
               preg_match('#^[a-z]+://#i', $url);
    }
    
    private static function is_data_url($url) {
        return strpos($url, 'data:') === 0;
    }
    
    public static function get_asset_info($extract_dir) {
        $asset_extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'otf', 'eot'];
        $asset_info = [
            'total_assets' => 0,
            'by_type' => [],
            'files' => []
        ];
        
        foreach ($asset_extensions as $ext) {
            $files = glob("$extract_dir/*.$ext");
            $subdir_files = glob("$extract_dir/**/*.$ext");
            $all_files = array_merge($files, $subdir_files);
            
            if (!empty($all_files)) {
                $asset_info['by_type'][$ext] = count($all_files);
                $asset_info['total_assets'] += count($all_files);
                
                foreach ($all_files as $file) {
                    $asset_info['files'][] = [
                        'filename' => basename($file),
                        'path' => str_replace($extract_dir, '', $file),
                        'size' => filesize($file),
                        'type' => $ext
                    ];
                }
            }
        }
        
        return $asset_info;
    }
    
    public static function cleanup_old_assets($days_to_keep = 30) {
        $upload_dir = wp_upload_dir();
        $assets_path = $upload_dir['basedir'] . '/html_import_assets';
        
        if (!file_exists($assets_path)) {
            return;
        }
        
        $files = glob("$assets_path/*");
        $cutoff_time = time() - ($days_to_keep * 24 * 60 * 60);
        $removed_files = [];
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff_time) {
                if (unlink($file)) {
                    $removed_files[] = basename($file);
                }
            }
        }
        
        return $removed_files;
    }
}