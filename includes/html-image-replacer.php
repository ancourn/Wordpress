<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HTML fragment image replacement functionality
 * Handles replacing image URLs in raw HTML content with WordPress attachment URLs
 */

class HTML_WP_HTML_Image_Replacer {
    
    /**
     * Replace image URLs in HTML fragment with attachment URLs
     * 
     * @param string $html_fragment HTML content to process
     * @param array $attachment_map Mapping of old URLs/basenames to new attachment data
     * @return string Processed HTML with replaced image URLs
     */
    public static function replace_images_in_html_fragment( $html_fragment, $attachment_map ) {
        // Replace img src attributes
        $html_fragment = self::replace_img_src_attributes( $html_fragment, $attachment_map );
        
        // Replace background-image URLs in inline styles
        $html_fragment = self::replace_background_image_urls( $html_fragment, $attachment_map );
        
        // Replace content URLs in CSS
        $html_fragment = self::replace_content_image_urls( $html_fragment, $attachment_map );
        
        // Replace image URLs in CSS url() functions
        $html_fragment = self::replace_css_url_functions( $html_fragment, $attachment_map );
        
        return $html_fragment;
    }
    
    /**
     * Replace img src attributes
     */
    private static function replace_img_src_attributes( $html_fragment, $attachment_map ) {
        return preg_replace_callback(
            '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
            function( $matches ) use ( $attachment_map ) {
                $old_url = $matches[1];
                $new_url = self::get_replacement_url( $old_url, $attachment_map );
                
                if ( $new_url ) {
                    // Replace src attribute
                    $new_img = str_replace( 'src="' . $old_url . '"', 'src="' . esc_url( $new_url ) . '"', $matches[0] );
                    $new_img = str_replace( "src='" . $old_url . "'", "src='" . esc_url( $new_url ) . "'", $new_img );
                    return $new_img;
                }
                
                return $matches[0];
            },
            $html_fragment
        );
    }
    
    /**
     * Replace background-image URLs in inline styles
     */
    private static function replace_background_image_urls( $html_fragment, $attachment_map ) {
        return preg_replace_callback(
            '/background-image:\s*url\(["\']?([^"\')]+)["\']?\)/i',
            function( $matches ) use ( $attachment_map ) {
                $old_url = $matches[1];
                $new_url = self::get_replacement_url( $old_url, $attachment_map );
                
                if ( $new_url ) {
                    return 'background-image: url("' . esc_url( $new_url ) . '")';
                }
                
                return $matches[0];
            },
            $html_fragment
        );
    }
    
    /**
     * Replace content URLs in CSS
     */
    private static function replace_content_image_urls( $html_fragment, $attachment_map ) {
        return preg_replace_callback(
            '/content:\s*url\(["\']?([^"\')]+)["\']?\)/i',
            function( $matches ) use ( $attachment_map ) {
                $old_url = $matches[1];
                $new_url = self::get_replacement_url( $old_url, $attachment_map );
                
                if ( $new_url ) {
                    return 'content: url("' . esc_url( $new_url ) . '")';
                }
                
                return $matches[0];
            },
            $html_fragment
        );
    }
    
    /**
     * Replace image URLs in CSS url() functions (more comprehensive)
     */
    private static function replace_css_url_functions( $html_fragment, $attachment_map ) {
        // This handles various CSS properties that use url()
        return preg_replace_callback(
            '/url\(["\']?([^"\')]+)["\']?\)/i',
            function( $matches ) use ( $attachment_map ) {
                $old_url = $matches[1];
                
                // Only process if it's an image URL
                if ( HTML_WP_Media_Handler::is_supported_image_url( $old_url ) ) {
                    $new_url = self::get_replacement_url( $old_url, $attachment_map );
                    
                    if ( $new_url ) {
                        return 'url("' . esc_url( $new_url ) . '")';
                    }
                }
                
                return $matches[0];
            },
            $html_fragment
        );
    }
    
    /**
     * Get replacement URL from attachment map
     */
    private static function get_replacement_url( $old_url, $attachment_map ) {
        if ( ! $old_url ) return null;
        
        // Try exact URL match first
        if ( isset( $attachment_map[$old_url] ) ) {
            return is_array( $attachment_map[$old_url] ) ? $attachment_map[$old_url]['url'] : $attachment_map[$old_url];
        }
        
        // Try basename match
        $basename = basename( parse_url( $old_url, PHP_URL_PATH ) );
        if ( isset( $attachment_map[$basename] ) ) {
            return is_array( $attachment_map[$basename] ) ? $attachment_map[$basename]['url'] : $attachment_map[$basename];
        }
        
        // Try URL without query parameters
        $url_without_query = preg_replace( '/\?.*$/', '', $old_url );
        if ( isset( $attachment_map[$url_without_query] ) ) {
            return is_array( $attachment_map[$url_without_query] ) ? $attachment_map[$url_without_query]['url'] : $attachment_map[$url_without_query];
        }
        
        // Try URL without fragments
        $url_without_fragment = preg_replace( '/#.*$/', '', $old_url );
        if ( isset( $attachment_map[$url_without_fragment] ) ) {
            return is_array( $attachment_map[$url_without_fragment] ) ? $attachment_map[$url_without_fragment]['url'] : $attachment_map[$url_without_fragment];
        }
        
        return null;
    }
    
    /**
     * Extract all image URLs from HTML fragment
     * 
     * @param string $html_fragment HTML content to analyze
     * @return array Array of unique image URLs found
     */
    public static function extract_image_urls_from_html( $html_fragment ) {
        return HTML_WP_Media_Handler::extract_image_urls_from_html( $html_fragment );
    }
    
    /**
     * Process HTML fragment and build attachment map
     * 
     * @param string $html_fragment HTML content to process
     * @param int $post_id Post ID for attachment context
     * @return array Attachment map and processed HTML
     */
    public static function process_html_fragment( $html_fragment, $post_id = 0 ) {
        // Extract image URLs from HTML
        $image_urls = self::extract_image_urls_from_html( $html_fragment );
        
        // Filter out local and data URLs
        $image_urls = array_filter( $image_urls, function( $url ) {
            return HTML_WP_Media_Handler::is_external_url( $url ) && 
                   ! HTML_WP_Media_Handler::is_data_url( $url );
        } );
        
        // Build attachment map
        require_once plugin_dir_path( __FILE__ ) . 'media-handler.php';
        $attachment_map = HTML_WP_Media_Handler::batch_sideload_images( $image_urls, $post_id );
        
        // Replace image URLs in HTML
        $processed_html = self::replace_images_in_html_fragment( $html_fragment, $attachment_map );
        
        return [
            'html' => $processed_html,
            'attachment_map' => $attachment_map,
            'processed_images' => count( $attachment_map )
        ];
    }
    
    /**
     * Replace images in HTML content with detailed logging
     * 
     * @param string $html_fragment HTML content to process
     * @param array $attachment_map Attachment mapping
     * @param array &$log Reference to log array for debugging
     * @return string Processed HTML
     */
    public static function replace_images_with_logging( $html_fragment, $attachment_map, &$log = [] ) {
        $original_html = $html_fragment;
        $processed_html = self::replace_images_in_html_fragment( $html_fragment, $attachment_map );
        
        // Log changes
        if ( $original_html !== $processed_html ) {
            $log['html_replacements'] = 'Images were replaced in HTML content';
            $log['attachment_map_size'] = count( $attachment_map );
        } else {
            $log['html_replacements'] = 'No images were replaced in HTML content';
        }
        
        return $processed_html;
    }
    
    /**
     * Create a simplified attachment map for HTML replacement (URL only)
     * 
     * @param array $full_attachment_map Full attachment map with IDs and URLs
     * @return array Simplified map with URLs only
     */
    public static function create_url_only_attachment_map( $full_attachment_map ) {
        $url_map = [];
        
        foreach ( $full_attachment_map as $key => $data ) {
            if ( is_array( $data ) && isset( $data['url'] ) ) {
                $url_map[$key] = $data['url'];
            } else {
                $url_map[$key] = $data;
            }
        }
        
        return $url_map;
    }
    
    /**
     * Validate HTML fragment after image replacement
     * 
     * @param string $html_fragment HTML content to validate
     * @return array Validation results
     */
    public static function validate_html_after_replacement( $html_fragment ) {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'image_count' => 0,
            'broken_images' => []
        ];
        
        // Count images
        preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html_fragment, $img_matches );
        $validation['image_count'] = isset( $img_matches[1] ) ? count( $img_matches[1] ) : 0;
        
        // Check for broken images (external URLs that weren't replaced)
        if ( isset( $img_matches[1] ) ) {
            foreach ( $img_matches[1] as $img_src ) {
                if ( HTML_WP_Media_Handler::is_external_url( $img_src ) ) {
                    $validation['broken_images'][] = $img_src;
                    $validation['warnings'][] = "External image URL not replaced: $img_src";
                }
            }
        }
        
        // Basic HTML validation
        if ( ! preg_match( '/^<[a-z]/i', trim( $html_fragment ) ) ) {
            $validation['warnings'][] = 'HTML fragment does not start with an HTML tag';
        }
        
        // Check for unclosed tags
        $open_tags = [];
        preg_match_all( '/<([a-z][a-z0-9]*)[^>]*>/i', $html_fragment, $open_matches );
        preg_match_all( '/<\/([a-z][a-z0-9]*)[^>]*>/i', $html_fragment, $close_matches );
        
        if ( isset( $open_matches[1] ) ) {
            foreach ( $open_matches[1] as $tag ) {
                if ( ! in_array( $tag, ['img', 'br', 'hr', 'input', 'meta'] ) ) {
                    $open_tags[] = $tag;
                }
            }
        }
        
        if ( isset( $close_matches[1] ) ) {
            foreach ( $close_matches[1] as $tag ) {
                $key = array_search( $tag, $open_tags );
                if ( $key !== false ) {
                    unset( $open_tags[$key] );
                }
            }
        }
        
        if ( ! empty( $open_tags ) ) {
            $validation['warnings'][] = 'Unclosed HTML tags detected: ' . implode( ', ', array_unique( $open_tags ) );
        }
        
        if ( ! empty( $validation['errors'] ) ) {
            $validation['valid'] = false;
        }
        
        return $validation;
    }
}