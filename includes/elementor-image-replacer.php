<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Elementor JSON image replacement functionality
 * Handles replacing image URLs in Elementor widget data with WordPress attachment IDs
 */

class HTML_WP_Elementor_Image_Replacer {
    
    /**
     * Replace image URLs in Elementor JSON data
     * 
     * @param array $elementor_array Elementor data array
     * @param int $post_id Post ID for attachment context
     * @param array $attachment_map Optional pre-built attachment map
     * @return array Modified Elementor data array
     */
    public static function replace_images_in_elementor_data( $elementor_array, $post_id = 0, $attachment_map = null ) {
        // If no attachment map provided, build it from Elementor data
        if ( $attachment_map === null ) {
            $attachment_map = self::build_attachment_map_from_elementor( $elementor_array, $post_id );
        }
        
        // Recursively walk Elementor structure
        $walk = function (&$node) use (&$walk, $post_id, $attachment_map ) {
            if ( ! is_array( $node ) ) return;
            
            // Handle different widget types with image support
            if ( isset( $node['elType'] ) && $node['elType'] === 'widget' && isset( $node['widgetType'] ) ) {
                $widget_type = $node['widgetType'];
                
                switch ( $widget_type ) {
                    case 'image':
                        self::replace_image_widget_images( $node, $attachment_map );
                        break;
                        
                    case 'image-gallery':
                        self::replace_image_gallery_images( $node, $attachment_map );
                        break;
                        
                    case 'video':
                        self::replace_video_widget_images( $node, $attachment_map );
                        break;
                        
                    case 'icon-box':
                    case 'flip-box':
                    case 'call-to-action':
                        self::replace_icon_widget_images( $node, $attachment_map );
                        break;
                        
                    case 'background':
                        self::replace_background_images( $node, $attachment_map );
                        break;
                }
                
                // Handle background images in any widget
                self::replace_widget_background_images( $node, $attachment_map );
            }
            
            // Handle section and column background images
            if ( isset( $node['elType'] ) && in_array( $node['elType'], ['section', 'column'] ) ) {
                self::replace_container_background_images( $node, $attachment_map );
            }
            
            // Walk children 'elements'
            if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
                foreach ( $node['elements'] as &$child ) {
                    $walk( $child );
                }
            }
        };
        
        // Top-level is array of sections
        foreach ( $elementor_array as &$section ) {
            $walk( $section );
        }
        
        return $elementor_array;
    }
    
    /**
     * Replace images in image widgets
     */
    private static function replace_image_widget_images( &$node, $attachment_map ) {
        if ( isset( $node['settings']['image']['url'] ) ) {
            $old_url = $node['settings']['image']['url'];
            if ( $old_url ) {
                $new_image_data = self::get_replacement_image( $old_url, $attachment_map );
                if ( $new_image_data ) {
                    $node['settings']['image'] = $new_image_data;
                }
            }
        }
    }
    
    /**
     * Replace images in image gallery widgets
     */
    private static function replace_image_gallery_images( &$node, $attachment_map ) {
        if ( isset( $node['settings']['gallery'] ) && is_array( $node['settings']['gallery'] ) ) {
            foreach ( $node['settings']['gallery'] as &$gallery_item ) {
                if ( isset( $gallery_item['url'] ) ) {
                    $old_url = $gallery_item['url'];
                    $new_image_data = self::get_replacement_image( $old_url, $attachment_map );
                    if ( $new_image_data ) {
                        $gallery_item = array_merge( $gallery_item, $new_image_data );
                    }
                }
            }
        }
    }
    
    /**
     * Replace images in video widgets (poster images)
     */
    private static function replace_video_widget_images( &$node, $attachment_map ) {
        if ( isset( $node['settings']['poster_image']['url'] ) ) {
            $old_url = $node['settings']['poster_image']['url'];
            if ( $old_url ) {
                $new_image_data = self::get_replacement_image( $old_url, $attachment_map );
                if ( $new_image_data ) {
                    $node['settings']['poster_image'] = $new_image_data;
                }
            }
        }
    }
    
    /**
     * Replace images in icon-based widgets
     */
    private static function replace_icon_widget_images( &$node, $attachment_map ) {
        // Handle selected icon image if it's an image
        if ( isset( $node['settings']['selected_icon']['value']['url'] ) ) {
            $old_url = $node['settings']['selected_icon']['value']['url'];
            if ( $old_url ) {
                $new_image_data = self::get_replacement_image( $old_url, $attachment_map );
                if ( $new_image_data ) {
                    $node['settings']['selected_icon']['value'] = $new_image_data;
                }
            }
        }
        
        // Handle background image in icon box
        if ( isset( $node['settings']['background_image']['url'] ) ) {
            $old_url = $node['settings']['background_image']['url'];
            if ( $old_url ) {
                $new_image_data = self::get_replacement_image( $old_url, $attachment_map );
                if ( $new_image_data ) {
                    $node['settings']['background_image'] = $new_image_data;
                }
            }
        }
    }
    
    /**
     * Replace background images in any widget
     */
    private static function replace_widget_background_images( &$node, $attachment_map ) {
        // Handle background image in widget settings
        if ( isset( $node['settings']['background_image']['url'] ) ) {
            $old_url = $node['settings']['background_image']['url'];
            if ( $old_url ) {
                $new_image_data = self::get_replacement_image( $old_url, $attachment_map );
                if ( $new_image_data ) {
                    $node['settings']['background_image'] = $new_image_data;
                }
            }
        }
        
        // Handle background image in style settings
        if ( isset( $node['settings']['background_background_image']['url'] ) ) {
            $old_url = $node['settings']['background_background_image']['url'];
            if ( $old_url ) {
                $new_image_data = self::get_replacement_image( $old_url, $attachment_map );
                if ( $new_image_data ) {
                    $node['settings']['background_background_image'] = $new_image_data;
                }
            }
        }
    }
    
    /**
     * Replace background images in sections and columns
     */
    private static function replace_container_background_images( &$node, $attachment_map ) {
        // Handle background image in container
        if ( isset( $node['settings']['background_image']['url'] ) ) {
            $old_url = $node['settings']['background_image']['url'];
            if ( $old_url ) {
                $new_image_data = self::get_replacement_image( $old_url, $attachment_map );
                if ( $new_image_data ) {
                    $node['settings']['background_image'] = $new_image_data;
                }
            }
        }
        
        // Handle background overlay image
        if ( isset( $node['settings']['background_overlay_image']['url'] ) ) {
            $old_url = $node['settings']['background_overlay_image']['url'];
            if ( $old_url ) {
                $new_image_data = self::get_replacement_image( $old_url, $attachment_map );
                if ( $new_image_data ) {
                    $node['settings']['background_overlay_image'] = $new_image_data;
                }
            }
        }
    }
    
    /**
     * Get replacement image data from attachment map
     */
    private static function get_replacement_image( $old_url, $attachment_map ) {
        if ( ! $old_url ) return null;
        
        // Try exact URL match first
        if ( isset( $attachment_map[$old_url] ) ) {
            return $attachment_map[$old_url];
        }
        
        // Try basename match
        $basename = basename( parse_url( $old_url, PHP_URL_PATH ) );
        if ( isset( $attachment_map[$basename] ) ) {
            return $attachment_map[$basename];
        }
        
        // Try URL without query parameters
        $url_without_query = preg_replace( '/\?.*$/', '', $old_url );
        if ( isset( $attachment_map[$url_without_query] ) ) {
            return $attachment_map[$url_without_query];
        }
        
        return null;
    }
    
    /**
     * Build attachment map from Elementor data
     */
    private static function build_attachment_map_from_elementor( $elementor_array, $post_id ) {
        $image_urls = [];
        
        // Extract all image URLs from Elementor data
        $extract_urls = function( $node ) use ( &$extract_urls, &$image_urls ) {
            if ( ! is_array( $node ) ) return;
            
            // Extract from various widget types
            if ( isset( $node['elType'] ) && $node['elType'] === 'widget' ) {
                // Image widget
                if ( isset( $node['settings']['image']['url'] ) ) {
                    $image_urls[] = $node['settings']['image']['url'];
                }
                
                // Image gallery
                if ( isset( $node['settings']['gallery'] ) && is_array( $node['settings']['gallery'] ) ) {
                    foreach ( $node['settings']['gallery'] as $gallery_item ) {
                        if ( isset( $gallery_item['url'] ) ) {
                            $image_urls[] = $gallery_item['url'];
                        }
                    }
                }
                
                // Video poster
                if ( isset( $node['settings']['poster_image']['url'] ) ) {
                    $image_urls[] = $node['settings']['poster_image']['url'];
                }
                
                // Icon images
                if ( isset( $node['settings']['selected_icon']['value']['url'] ) ) {
                    $image_urls[] = $node['settings']['selected_icon']['value']['url'];
                }
                
                // Background images
                if ( isset( $node['settings']['background_image']['url'] ) ) {
                    $image_urls[] = $node['settings']['background_image']['url'];
                }
                if ( isset( $node['settings']['background_background_image']['url'] ) ) {
                    $image_urls[] = $node['settings']['background_background_image']['url'];
                }
            }
            
            // Container background images
            if ( isset( $node['elType'] ) && in_array( $node['elType'], ['section', 'column'] ) ) {
                if ( isset( $node['settings']['background_image']['url'] ) ) {
                    $image_urls[] = $node['settings']['background_image']['url'];
                }
                if ( isset( $node['settings']['background_overlay_image']['url'] ) ) {
                    $image_urls[] = $node['settings']['background_overlay_image']['url'];
                }
            }
            
            // Recurse into children
            if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
                foreach ( $node['elements'] as $child ) {
                    $extract_urls( $child );
                }
            }
        };
        
        foreach ( $elementor_array as $section ) {
            $extract_urls( $section );
        }
        
        // Remove duplicates and process URLs
        $image_urls = array_filter( array_unique( $image_urls ) );
        $image_urls = array_filter( $image_urls, function( $url ) {
            return ! empty( $url ) && HTML_WP_Media_Handler::is_external_url( $url );
        } );
        
        // Build attachment map using media handler
        require_once plugin_dir_path( __FILE__ ) . 'media-handler.php';
        return HTML_WP_Media_Handler::batch_sideload_images( $image_urls, $post_id );
    }
    
    /**
     * Extract all image URLs from Elementor data (for debugging)
     */
    public static function extract_all_image_urls( $elementor_array ) {
        $image_urls = [];
        
        $extract_urls = function( $node ) use ( &$extract_urls, &$image_urls ) {
            if ( ! is_array( $node ) ) return;
            
            // Extract all URL-like strings from the node
            array_walk_recursive( $node, function( $value, $key ) use ( &$image_urls ) {
                if ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
                    if ( HTML_WP_Media_Handler::is_supported_image_url( $value ) ) {
                        $image_urls[] = $value;
                    }
                }
            } );
            
            // Recurse into children
            if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
                foreach ( $node['elements'] as $child ) {
                    $extract_urls( $child );
                }
            }
        };
        
        foreach ( $elementor_array as $section ) {
            $extract_urls( $section );
        }
        
        return array_unique( $image_urls );
    }
}