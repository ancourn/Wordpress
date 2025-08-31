<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Contact Form 7 integration for HTML to WordPress Importer
 * Handles automatic creation of Contact Form 7 forms from HTML forms
 */

class HTML_WP_CF7_Integration {
    
    /**
     * Check if Contact Form 7 is active
     * 
     * @return bool True if CF7 is active
     */
    public static function is_cf7_active() {
        return defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' );
    }
    
    /**
     * Create Contact Form 7 form from HTML form
     * 
     * @param string $form_html HTML form content
     * @param string $title Form title
     * @param array $options Additional options
     * @return string|false CF7 shortcode on success, false on failure
     */
    public static function create_cf7_from_html( $form_html, $title = 'Imported Form', $options = [] ) {
        if ( ! self::is_cf7_active() ) {
            return false;
        }
        
        // Extract form data
        $form_data = HTML_WP_Form_Handler::extract_form_attributes( $form_html );
        $fields = HTML_WP_Form_Handler::extract_form_fields( $form_html );
        
        // Build CF7 form body
        $form_body = self::build_cf7_form_body( $fields, $options );
        
        // Create CF7 post
        $post_id = wp_insert_post( [
            'post_title' => wp_strip_all_tags( $title ),
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'publish',
            'post_content' => $form_body,
        ] );
        
        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return false;
        }
        
        // Set CF7 meta data
        self::set_cf7_meta_data( $post_id, $form_data, $options );
        
        // Return CF7 shortcode
        return '[contact-form-7 id="' . $post_id . '" title="' . esc_attr( $title ) . '"]';
    }
    
    /**
     * Build CF7 form body from HTML fields
     * 
     * @param array $fields Form fields data
     * @param array $options Additional options
     * @return string CF7 form body
     */
    private static function build_cf7_form_body( $fields, $options = [] ) {
        $form_body = '';
        $has_submit = false;
        
        foreach ( $fields as $field ) {
            if ( empty( $field['name'] ) ) {
                continue;
            }
            
            $field_type = isset( $field['type'] ) ? $field['type'] : 'text';
            $field_name = sanitize_text_field( $field['name'] );
            $field_label = ! empty( $field['placeholder'] ) ? $field['placeholder'] : ucfirst( $field_name );
            $required = isset( $field['required'] ) && $field['required'] === 'required' ? '*' : '';
            
            switch ( $field_type ) {
                case 'text':
                case 'email':
                case 'tel':
                case 'url':
                case 'number':
                    $cf7_type = in_array( $field_type, ['email', 'tel', 'url', 'number'] ) ? $field_type : 'text';
                    $form_body .= '<p>' . $field_label . $required . '<br />' . "\n";
                    $form_body .= '[' . $cf7_type . ' ' . $field_name;
                    if ( ! empty( $field['placeholder'] ) ) {
                        $form_body .= ' placeholder "' . esc_attr( $field['placeholder'] ) . '"';
                    }
                    if ( $required ) {
                        $form_body .= ' required';
                    }
                    $form_body .= ']</p>' . "\n";
                    break;
                    
                case 'textarea':
                    $form_body .= '<p>' . $field_label . $required . '<br />' . "\n";
                    $form_body .= '[textarea ' . $field_name;
                    if ( ! empty( $field['placeholder'] ) ) {
                        $form_body .= ' placeholder "' . esc_attr( $field['placeholder'] ) . '"';
                    }
                    if ( $required ) {
                        $form_body .= ' required';
                    }
                    $form_body .= ']</p>' . "\n";
                    break;
                    
                case 'select':
                    $form_body .= '<p>' . $field_label . $required . '<br />' . "\n";
                    $form_body .= '[select ' . $field_name;
                    if ( $required ) {
                        $form_body .= ' required';
                    }
                    $form_body .= ']' . "\n";
                    
                    if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
                        foreach ( $field['options'] as $option ) {
                            if ( ! empty( $option['value'] ) ) {
                                $option_label = ! empty( $option['label'] ) ? $option['label'] : $option['value'];
                                $form_body .= ' "' . esc_attr( $option_label ) . '"';
                            }
                        }
                    }
                    
                    $form_body .= '[/select]</p>' . "\n";
                    break;
                    
                case 'checkbox':
                case 'radio':
                    $form_body .= '<p>' . $field_label . '<br />' . "\n";
                    $form_body .= '[' . $field_type . ' ' . $field_name . ' use_label_element';
                    if ( $required ) {
                        $form_body .= ' required';
                    }
                    $form_body .= ' "Default"]</p>' . "\n";
                    break;
                    
                case 'submit':
                case 'button':
                    $button_text = ! empty( $field['value'] ) ? $field['value'] : 'Submit';
                    $form_body .= '<p>[submit "' . esc_attr( $button_text ) . '"]</p>' . "\n";
                    $has_submit = true;
                    break;
            }
        }
        
        // Add submit button if none found
        if ( ! $has_submit ) {
            $submit_text = isset( $options['submit_text'] ) ? $options['submit_text'] : 'Submit';
            $form_body .= '<p>[submit "' . esc_attr( $submit_text ) . '"]</p>' . "\n";
        }
        
        return $form_body;
    }
    
    /**
     * Set CF7 meta data
     * 
     * @param int $post_id CF7 post ID
     * @param array $form_data Form data
     * @param array $options Additional options
     */
    private static function set_cf7_meta_data( $post_id, $form_data, $options = [] ) {
        // Mail settings
        $mail_subject = isset( $options['mail_subject'] ) ? $options['mail_subject'] : 'Contact Form Submission';
        $mail_sender = isset( $options['mail_sender'] ) ? $options['mail_sender'] : '[your-name] <[your-email]>';
        $mail_body = isset( $options['mail_body'] ) ? $options['mail_body'] : "From: [your-name] <[your-email]>\n\nSubject: [your-subject]\n\nMessage:\n[message]";
        
        $mail_data = [
            'subject' => $mail_subject,
            'sender' => $mail_sender,
            'body' => $mail_body,
            'recipient' => isset( $options['mail_recipient'] ) ? $options['mail_recipient'] : get_option( 'admin_email' ),
            'additional_headers' => '',
            'attachments' => '',
            'use_html' => 0,
            'exclude_blank' => 0,
        ];
        
        update_post_meta( $post_id, '_mail', $mail_data );
        
        // Mail (2) settings - copy to sender
        $mail_2_data = [
            'active' => isset( $options['mail_2_active'] ) ? $options['mail_2_active'] : false,
            'subject' => isset( $options['mail_2_subject'] ) ? $options['mail_2_subject'] : '[your-subject] copy',
            'sender' => isset( $options['mail_2_sender'] ) ? $options['mail_2_sender'] : '[your-name] <[your-email]>',
            'body' => isset( $options['mail_2_body'] ) ? $options['mail_2_body'] : $mail_body,
            'recipient' => isset( $options['mail_2_recipient'] ) ? $options['mail_2_recipient'] : '[your-email]',
            'additional_headers' => '',
            'attachments' => '',
            'use_html' => 0,
            'exclude_blank' => 0,
        ];
        
        update_post_meta( $post_id, '_mail_2', $mail_2_data );
        
        // Messages
        $messages = [
            'mail_sent_ok' => isset( $options['message_sent'] ) ? $options['message_sent'] : 'Thank you for your message. It has been sent.',
            'mail_sent_ng' => isset( $options['message_not_sent'] ) ? $options['message_not_sent'] : 'There was an error trying to send your message. Please try again later.',
            'validation_error' => isset( $options['validation_error'] ) ? $options['validation_error'] : 'One or more fields have an error. Please check and try again.',
            'spam' => isset( $options['spam_detected'] ) ? $options['spam_detected'] : 'There was an error trying to send your message. Please try again later.',
            'invalid_required' => isset( $options['required_field'] ) ? $options['required_field'] : 'The field is required.',
            'invalid_too_short' => isset( $options['too_short'] ) ? $options['too_short'] : 'The field is too short.',
            'invalid_too_long' => isset( $options['too_long'] ) ? $options['too_long'] : 'The field is too long.',
            'invalid_email' => isset( $options['invalid_email'] ) ? $options['invalid_email'] : 'The email address entered is invalid.',
        ];
        
        update_post_meta( $post_id, '_messages', $messages );
        
        // Additional settings
        $additional_settings = isset( $options['additional_settings'] ) ? $options['additional_settings'] : '';
        update_post_meta( $post_id, '_additional_settings', $additional_settings );
    }
    
    /**
     * Process forms and create CF7 forms
     * 
     * @param array $forms Form data from HTML_WP_Form_Handler
     * @param int $post_id Post ID
     * @param array $options Processing options
     * @return array Processing results
     */
    public static function process_forms_to_cf7( $forms, $post_id, $options = [] ) {
        $results = [
            'total_forms' => count( $forms ),
            'cf7_forms_created' => 0,
            'cf7_shortcodes' => [],
            'processed_forms' => [],
            'errors' => [],
        ];
        
        if ( ! self::is_cf7_active() ) {
            $results['errors'][] = 'Contact Form 7 is not active';
            return $results;
        }
        
        $processed_forms = [];
        
        foreach ( $forms as $index => $form ) {
            $form_title = ! empty( $form['attributes']['id'] ) ? 
                         'Imported Form - ' . $form['attributes']['id'] : 
                         'Imported Form ' . ( $index + 1 );
            
            $cf7_shortcode = self::create_cf7_from_html( $form['raw_html'], $form_title, $options );
            
            if ( $cf7_shortcode ) {
                $results['cf7_forms_created']++;
                $results['cf7_shortcodes'][] = $cf7_shortcode;
                
                $processed_form = $form;
                $processed_form['cf7_shortcode'] = $cf7_shortcode;
                $processed_form['cf7_title'] = $form_title;
                $processed_forms[] = $processed_form;
            } else {
                $results['errors'][] = 'Failed to create CF7 form for: ' . $form_title;
            }
        }
        
        // Store processed forms data
        if ( ! empty( $processed_forms ) ) {
            update_post_meta( $post_id, '_htwpi_processed_forms', $processed_forms );
        }
        
        $results['processed_forms'] = $processed_forms;
        
        return $results;
    }
    
    /**
     * Replace forms in HTML with CF7 shortcodes
     * 
     * @param string $html HTML content
     * @param int $post_id Post ID
     * @param array $options Processing options
     * @return array Processing results
     */
    public static function replace_html_forms_with_cf7( $html, $post_id, $options = [] ) {
        $results = [
            'forms_found' => 0,
            'cf7_forms_created' => 0,
            'html_modified' => false,
            'processed_html' => $html,
            'cf7_shortcodes' => [],
            'errors' => [],
        ];
        
        // Extract forms from HTML
        $forms = HTML_WP_Form_Handler::extract_forms_from_html( $html );
        $results['forms_found'] = count( $forms );
        
        if ( empty( $forms ) ) {
            return $results;
        }
        
        // Process forms to CF7
        $cf7_results = self::process_forms_to_cf7( $forms, $post_id, $options );
        $results['cf7_forms_created'] = $cf7_results['cf7_forms_created'];
        $results['cf7_shortcodes'] = $cf7_results['cf7_shortcodes'];
        $results['errors'] = $cf7_results['errors'];
        
        // Replace forms with CF7 shortcodes
        $processed_html = $html;
        foreach ( $forms as $index => $form ) {
            if ( isset( $cf7_results['processed_forms'][$index]['cf7_shortcode'] ) ) {
                $cf7_shortcode = $cf7_results['processed_forms'][$index]['cf7_shortcode'];
                $processed_html = str_replace( $form['raw_html'], $cf7_shortcode, $processed_html );
            }
        }
        
        $results['html_modified'] = ( $processed_html !== $html );
        $results['processed_html'] = $processed_html;
        
        return $results;
    }
    
    /**
     * Get CF7 forms created for a post
     * 
     * @param int $post_id Post ID
     * @return array CF7 forms data
     */
    public static function get_post_cf7_forms( $post_id ) {
        $processed_forms = get_post_meta( $post_id, '_htwpi_processed_forms', true );
        return is_array( $processed_forms ) ? $processed_forms : [];
    }
    
    /**
     * Get default CF7 options
     * 
     * @return array Default options
     */
    public static function get_default_options() {
        return [
            'mail_subject' => 'Contact Form Submission from [your-name]',
            'mail_sender' => '[your-name] <[your-email]>',
            'mail_body' => "From: [your-name] <[your-email]>\n\nSubject: [your-subject]\n\nMessage:\n[message]",
            'mail_recipient' => get_option( 'admin_email' ),
            'mail_2_active' => false,
            'mail_2_subject' => '[your-subject] copy',
            'mail_2_sender' => '[your-name] <[your-email]>',
            'mail_2_body' => "From: [your-name] <[your-email]>\n\nSubject: [your-subject]\n\nMessage:\n[message]",
            'mail_2_recipient' => '[your-email]',
            'message_sent' => 'Thank you for your message. It has been sent.',
            'message_not_sent' => 'There was an error trying to send your message. Please try again later.',
            'validation_error' => 'One or more fields have an error. Please check and try again.',
            'spam_detected' => 'There was an error trying to send your message. Please try again later.',
            'required_field' => 'The field is required.',
            'too_short' => 'The field is too short.',
            'too_long' => 'The field is too long.',
            'invalid_email' => 'The email address entered is invalid.',
            'submit_text' => 'Submit',
            'additional_settings' => '',
        ];
    }
    
    /**
     * Validate if form can be converted to CF7
     * 
     * @param array $form Form data
     * @return array Validation results
     */
    public static function validate_form_for_cf7( $form ) {
        $validation = [
            'valid' => true,
            'warnings' => [],
            'supported_fields' => 0,
            'unsupported_fields' => 0,
        ];
        
        if ( empty( $form['fields'] ) ) {
            $validation['warnings'][] = 'No form fields found';
            return $validation;
        }
        
        $supported_field_types = ['text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'checkbox', 'radio', 'submit', 'button'];
        
        foreach ( $form['fields'] as $field ) {
            $field_type = isset( $field['type'] ) ? $field['type'] : 'text';
            
            if ( in_array( $field_type, $supported_field_types ) ) {
                $validation['supported_fields']++;
            } else {
                $validation['unsupported_fields']++;
                $validation['warnings'][] = "Unsupported field type: {$field_type}";
            }
        }
        
        if ( $validation['unsupported_fields'] > 0 ) {
            $validation['warnings'][] = 'Form contains unsupported field types that will be ignored';
        }
        
        if ( empty( $validation['supported_fields'] ) ) {
            $validation['valid'] = false;
            $validation['warnings'][] = 'No supported form fields found';
        }
        
        return $validation;
    }
}