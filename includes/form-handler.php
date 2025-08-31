<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Form handling functionality for HTML to WordPress Importer
 * Handles detecting, storing, and replacing HTML forms with WordPress-compatible solutions
 */

class HTML_WP_Form_Handler {
    
    /**
     * Register the raw form shortcode
     */
    public static function register_shortcodes() {
        add_shortcode( 'htwpi_raw_form', [ __CLASS__, 'raw_form_shortcode' ] );
        add_shortcode( 'htwpi_imported_form', [ __CLASS__, 'imported_form_shortcode' ] );
    }
    
    /**
     * Raw form shortcode callback
     * Outputs the raw HTML form stored in post meta
     */
    public static function raw_form_shortcode( $atts ) {
        $atts = shortcode_atts(
            [
                'id' => 0,
                'index' => 0,
                'post_id' => 0,
            ],
            $atts,
            'htwpi_raw_form'
        );
        
        $post_id = ! empty( $atts['post_id'] ) ? intval( $atts['post_id'] ) : intval( $atts['id'] );
        $form_index = intval( $atts['index'] );
        
        if ( ! $post_id ) {
            return '<!-- HTWPI: Missing post_id attribute -->';
        }
        
        $forms = get_post_meta( $post_id, '_htwpi_raw_forms', true );
        
        if ( empty( $forms ) || ! is_array( $forms ) ) {
            return '<!-- HTWPI: No forms found for post ' . $post_id . ' -->';
        }
        
        if ( ! isset( $forms[$form_index] ) ) {
            return '<!-- HTWPI: Form index ' . $form_index . ' not found for post ' . $post_id . ' -->';
        }
        
        // Apply filters to allow modification of form HTML
        $form_html = apply_filters( 'htwpi_raw_form_html', $forms[$form_index], $post_id, $form_index );
        
        return $form_html;
    }
    
    /**
     * Imported form shortcode callback
     * Outputs the processed form (Contact Form 7, etc.)
     */
    public static function imported_form_shortcode( $atts ) {
        $atts = shortcode_atts(
            [
                'id' => 0,
                'index' => 0,
                'post_id' => 0,
                'type' => 'raw',
            ],
            $atts,
            'htwpi_imported_form'
        );
        
        $post_id = ! empty( $atts['post_id'] ) ? intval( $atts['post_id'] ) : intval( $atts['id'] );
        $form_index = intval( $atts['index'] );
        $form_type = sanitize_text_field( $atts['type'] );
        
        if ( ! $post_id ) {
            return '<!-- HTWPI: Missing post_id attribute -->';
        }
        
        $forms = get_post_meta( $post_id, '_htwpi_processed_forms', true );
        
        if ( empty( $forms ) || ! is_array( $forms ) ) {
            return '<!-- HTWPI: No processed forms found for post ' . $post_id . ' -->';
        }
        
        if ( ! isset( $forms[$form_index] ) ) {
            return '<!-- HTWPI: Form index ' . $form_index . ' not found for post ' . $post_id . ' -->';
        }
        
        $form_data = $forms[$form_index];
        
        // Return the appropriate form based on type
        switch ( $form_type ) {
            case 'cf7':
                if ( isset( $form_data['cf7_shortcode'] ) ) {
                    return do_shortcode( $form_data['cf7_shortcode'] );
                }
                break;
                
            case 'raw':
            default:
                if ( isset( $form_data['raw_html'] ) ) {
                    return $form_data['raw_html'];
                }
                break;
        }
        
        return '<!-- HTWPI: Form type ' . $form_type . ' not available for index ' . $form_index . ' -->';
    }
    
    /**
     * Extract forms from HTML content
     * 
     * @param string $html HTML content to extract forms from
     * @return array Array of extracted form data
     */
    public static function extract_forms_from_html( $html ) {
        $forms = [];
        
        // Extract form tags
        preg_match_all( '/<form[^>]*>(.*?)<\/form>/is', $html, $form_matches );
        
        if ( isset( $form_matches[0] ) && isset( $form_matches[1] ) ) {
            foreach ( $form_matches[0] as $index => $form_html ) {
                $form_content = $form_matches[1][$index];
                
                // Extract form attributes
                $form_attributes = self::extract_form_attributes( $form_html );
                
                // Extract form fields
                $fields = self::extract_form_fields( $form_content );
                
                $forms[] = [
                    'raw_html' => $form_html,
                    'attributes' => $form_attributes,
                    'fields' => $fields,
                    'index' => $index,
                ];
            }
        }
        
        return $forms;
    }
    
    /**
     * Extract form attributes from form HTML
     * 
     * @param string $form_html Form HTML tag
     * @return array Form attributes
     */
    private static function extract_form_attributes( $form_html ) {
        $attributes = [];
        
        // Extract action attribute
        preg_match( '/action=["\']([^"\']*)["\']/i', $form_html, $action_match );
        $attributes['action'] = isset( $action_match[1] ) ? $action_match[1] : '';
        
        // Extract method attribute
        preg_match( '/method=["\']([^"\']*)["\']/i', $form_html, $method_match );
        $attributes['method'] = isset( $method_match[1] ) ? strtolower( $method_match[1] ) : 'post';
        
        // Extract id attribute
        preg_match( '/id=["\']([^"\']*)["\']/i', $form_html, $id_match );
        $attributes['id'] = isset( $id_match[1] ) ? $id_match[1] : '';
        
        // Extract class attribute
        preg_match( '/class=["\']([^"\']*)["\']/i', $form_html, $class_match );
        $attributes['class'] = isset( $class_match[1] ) ? $class_match[1] : '';
        
        // Extract enctype attribute
        preg_match( '/enctype=["\']([^"\']*)["\']/i', $form_html, $enctype_match );
        $attributes['enctype'] = isset( $enctype_match[1] ) ? $enctype_match[1] : '';
        
        return $attributes;
    }
    
    /**
     * Extract form fields from form content
     * 
     * @param string $form_content Form content (inside form tags)
     * @return array Form fields data
     */
    private static function extract_form_fields( $form_content ) {
        $fields = [];
        
        // Extract input fields
        preg_match_all( '/<input[^>]*>/i', $form_content, $input_matches );
        if ( isset( $input_matches[0] ) ) {
            foreach ( $input_matches[0] as $input ) {
                $field_data = self::extract_input_attributes( $input );
                if ( $field_data ) {
                    $fields[] = $field_data;
                }
            }
        }
        
        // Extract textarea fields
        preg_match_all( '/<textarea[^>]*>(.*?)<\/textarea>/is', $form_content, $textarea_matches );
        if ( isset( $textarea_matches[0] ) ) {
            foreach ( $textarea_matches[0] as $index => $textarea ) {
                $field_data = self::extract_input_attributes( $textarea );
                $field_data['value'] = $textarea_matches[1][$index];
                $field_data['type'] = 'textarea';
                $fields[] = $field_data;
            }
        }
        
        // Extract select fields
        preg_match_all( '/<select[^>]*>(.*?)<\/select>/is', $form_content, $select_matches );
        if ( isset( $select_matches[0] ) ) {
            foreach ( $select_matches[0] as $index => $select ) {
                $field_data = self::extract_input_attributes( $select );
                $field_data['type'] = 'select';
                $field_data['options'] = self::extract_select_options( $select_matches[1][$index] );
                $fields[] = $field_data;
            }
        }
        
        // Extract button fields
        preg_match_all( '/<button[^>]*>(.*?)<\/button>/is', $form_content, $button_matches );
        if ( isset( $button_matches[0] ) ) {
            foreach ( $button_matches[0] as $button ) {
                $field_data = self::extract_input_attributes( $button );
                $field_data['type'] = 'button';
                $field_data['value'] = $button_matches[1][0] ?? '';
                $fields[] = $field_data;
            }
        }
        
        return $fields;
    }
    
    /**
     * Extract attributes from input/textarea/select elements
     * 
     * @param string $element_html Element HTML
     * @return array Element attributes
     */
    private static function extract_input_attributes( $element_html ) {
        $attributes = [];
        
        // Common attributes
        $attr_names = ['name', 'type', 'id', 'class', 'value', 'placeholder', 'required', 'disabled', 'readonly'];
        
        foreach ( $attr_names as $attr_name ) {
            preg_match( '/' . $attr_name . '=["\']([^"\']*)["\']/i', $element_html, $match );
            $attributes[$attr_name] = isset( $match[1] ) ? $match[1] : '';
        }
        
        return $attributes;
    }
    
    /**
     * Extract options from select element
     * 
     * @param string $select_content Select element content
     * @return array Options data
     */
    private static function extract_select_options( $select_content ) {
        $options = [];
        
        preg_match_all( '/<option[^>]*>(.*?)<\/option>/i', $select_content, $option_matches );
        
        if ( isset( $option_matches[0] ) && isset( $option_matches[1] ) ) {
            foreach ( $option_matches[0] as $index => $option ) {
                $option_data = self::extract_input_attributes( $option );
                $option_data['label'] = $option_matches[1][$index];
                $options[] = $option_data;
            }
        }
        
        return $options;
    }
    
    /**
     * Replace forms in HTML with shortcodes
     * 
     * @param string $html HTML content to process
     * @param int $post_id Post ID for form storage
     * @return string Processed HTML with form replacements
     */
    public static function replace_forms_with_shortcodes( $html, $post_id ) {
        // Extract forms from HTML
        $forms = self::extract_forms_from_html( $html );
        
        if ( empty( $forms ) ) {
            return $html;
        }
        
        // Store forms in post meta
        update_post_meta( $post_id, '_htwpi_raw_forms', wp_list_pluck( $forms, 'raw_html' ) );
        
        // Replace each form with shortcode
        foreach ( $forms as $index => $form ) {
            $shortcode = '[htwpi_raw_form post_id="' . $post_id . '" index="' . $index . '"]';
            $html = str_replace( $form['raw_html'], $shortcode, $html );
        }
        
        return $html;
    }
    
    /**
     * Process forms for a post (extract, store, replace)
     * 
     * @param string $html HTML content
     * @param int $post_id Post ID
     * @return array Processing results
     */
    public static function process_post_forms( $html, $post_id ) {
        $results = [
            'forms_found' => 0,
            'forms_processed' => 0,
            'html_modified' => false,
            'processed_html' => $html,
        ];
        
        $forms = self::extract_forms_from_html( $html );
        $results['forms_found'] = count( $forms );
        
        if ( ! empty( $forms ) ) {
            // Store forms in post meta
            update_post_meta( $post_id, '_htwpi_raw_forms', wp_list_pluck( $forms, 'raw_html' ) );
            
            // Replace forms with shortcodes
            $processed_html = self::replace_forms_with_shortcodes( $html, $post_id );
            
            $results['forms_processed'] = count( $forms );
            $results['html_modified'] = ( $processed_html !== $html );
            $results['processed_html'] = $processed_html;
        }
        
        return $results;
    }
    
    /**
     * Get forms for a post
     * 
     * @param int $post_id Post ID
     * @return array Form data
     */
    public static function get_post_forms( $post_id ) {
        $raw_forms = get_post_meta( $post_id, '_htwpi_raw_forms', true );
        $processed_forms = get_post_meta( $post_id, '_htwpi_processed_forms', true );
        
        return [
            'raw_forms' => is_array( $raw_forms ) ? $raw_forms : [],
            'processed_forms' => is_array( $processed_forms ) ? $processed_forms : [],
        ];
    }
    
    /**
     * Delete form data for a post
     * 
     * @param int $post_id Post ID
     */
    public static function delete_post_forms( $post_id ) {
        delete_post_meta( $post_id, '_htwpi_raw_forms' );
        delete_post_meta( $post_id, '_htwpi_processed_forms' );
    }
    
    /**
     * Check if HTML contains forms
     * 
     * @param string $html HTML content
     * @return bool True if forms are found
     */
    public static function has_forms( $html ) {
        return stripos( $html, '<form' ) !== false;
    }
    
    /**
     * Count forms in HTML
     * 
     * @param string $html HTML content
     * @return int Number of forms found
     */
    public static function count_forms( $html ) {
        preg_match_all( '/<form[^>]*>/i', $html, $matches );
        return isset( $matches[0] ) ? count( $matches[0] ) : 0;
    }
}