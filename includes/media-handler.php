<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Media sideload helper functions for HTML to WordPress Importer
 * Handles downloading external images and creating WordPress attachments
 */

class HTML_WP_Media_Handler {
    
    /**
     * Sideload an image from URL and create WordPress attachment
     * 
     * @param string $image_url The URL of the image to download
     * @param int $post_id Optional post ID to attach the image to
     * @return int|WP_Error Attachment ID on success, WP_Error on failure
     */
    public static function sideload_image_get_attachment_id( $image_url, $post_id = 0 ) {
        // Load required WordPress files
        if ( ! function_exists('media_sideload_image') ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        
        // If already local URL, try to find attachment by URL
        $upload_dir = wp_upload_dir();
        if (strpos($image_url, $upload_dir['baseurl']) !== false) {
            $attachment_id = attachment_url_to_postid($image_url);
            if ($attachment_id) return $attachment_id;
        }
        
        // Download to tmp
        $tmp = download_url( $image_url );
        if ( is_wp_error( $tmp ) ) {
            return $tmp;
        }
        
        $file_array = array();
        preg_match('/[^\?]+\.(jpg|jpeg|png|gif|webp|svg)/i', $image_url, $matches);
        $file_array['name'] = isset($matches[0]) ? basename($matches[0]) : wp_basename($image_url);
        $file_array['tmp_name'] = $tmp;
        
        // Do the sideload
        $attachment_id = media_handle_sideload( $file_array, $post_id );
        
        // Clean up if error
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $file_array['tmp_name'] );
            return $attachment_id;
        }
        
        return $attachment_id;
    }
    
    /**
     * Batch process multiple image URLs
     * 
     * @param array $image_urls Array of image URLs to process
     * @param int $post_id Optional post ID to attach images to
     * @return array Array mapping old URLs to new attachment IDs and URLs
     */
    public static function batch_sideload_images( $image_urls, $post_id = 0 ) {
        $attachment_map = [];
        $processed_urls = [];
        
        foreach ( $image_urls as $url ) {
            // Skip if already processed
            if ( isset( $processed_urls[$url] ) ) {
                continue;
            }
            
            $attachment_id = self::sideload_image_get_attachment_id( $url, $post_id );
            
            if ( ! is_wp_error( $attachment_id ) ) {
                $new_url = wp_get_attachment_url( $attachment_id );
                $basename = basename( parse_url( $url, PHP_URL_PATH ) );
                
                // Store multiple mappings for flexibility
                $attachment_map[$url] = [
                    'id' => $attachment_id,
                    'url' => $new_url
                ];
                $attachment_map[$basename] = [
                    'id' => $attachment_id,
                    'url' => $new_url
                ];
                
                $processed_urls[$url] = true;
            }
        }
        
        return $attachment_map;
    }
    
    /**
     * Extract image URLs from HTML content
     * 
     * @param string $html HTML content to extract images from
     * @return array Array of unique image URLs found
     */
    public static function extract_image_urls_from_html( $html ) {
        $image_urls = [];
        
        // Extract img src attributes
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches );
        if ( isset( $matches[1] ) ) {
            $image_urls = array_merge( $image_urls, $matches[1] );
        }
        
        // Extract background-image URLs from inline styles
        preg_match_all('/background-image:\s*url\(["\']?([^"\')]+)["\']?\)/i', $html, $matches );
        if ( isset( $matches[1] ) ) {
            $image_urls = array_merge( $image_urls, $matches[1] );
        }
        
        // Extract content URLs in CSS
        preg_match_all('/content:\s*url\(["\']?([^"\')]+)["\']?\)/i', $html, $matches );
        if ( isset( $matches[1] ) ) {
            $image_urls = array_merge( $image_urls, $matches[1] );
        }
        
        // Remove duplicates and filter out data URLs and empty values
        $image_urls = array_filter( array_unique( $image_urls ), function( $url ) {
            return ! empty( $url ) && strpos( $url, 'data:' ) !== 0;
        } );
        
        return array_values( $image_urls );
    }
    
    /**
     * Check if a URL is an external URL
     * 
     * @param string $url URL to check
     * @return bool True if external, false if local
     */
    public static function is_external_url( $url ) {
        return preg_match('#^(https?:)?//#i', $url) || 
               preg_match('#^[a-z]+://#i', $url);
    }
    
    /**
     * Check if a URL is a data URL
     * 
     * @param string $url URL to check
     * @return bool True if data URL, false otherwise
     */
    public static function is_data_url( $url ) {
        return strpos( $url, 'data:' ) === 0;
    }
    
    /**
     * Get image file extension from URL
     * 
     * @param string $url Image URL
     * @return string|false File extension or false if not found
     */
    public static function get_image_extension( $url ) {
        preg_match('/\.(jpg|jpeg|png|gif|webp|svg)/i', $url, $matches );
        return isset( $matches[1] ) ? strtolower( $matches[1] ) : false;
    }
    
    /**
     * Validate if URL points to a supported image format
     * 
     * @param string $url URL to validate
     * @return bool True if supported image format, false otherwise
     */
    public static function is_supported_image_url( $url ) {
        $extension = self::get_image_extension( $url );
        $supported = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        return $extension && in_array( $extension, $supported );
    }
}