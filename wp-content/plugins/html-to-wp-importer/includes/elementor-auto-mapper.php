<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Elementor_AutoMapper {
    
    public static function map_html_to_elementor($html) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $sections = [];
        $body = $dom->getElementsByTagName('body')->item(0);
        
        if ($body) {
            $sections = self::process_node($body);
        }
        
        return $sections;
    }
    
    private static function process_node($node) {
        $sections = [];
        $current_section = null;
        $current_column = null;
        
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $tagName = strtolower($child->tagName);
                
                // Handle section-level elements
                if (self::is_section_element($tagName)) {
                    if ($current_section) {
                        $sections[] = $current_section;
                    }
                    $current_section = self::create_section($child);
                    $current_column = null;
                }
                // Handle column-level elements
                elseif (self::is_column_element($tagName)) {
                    if ($current_section) {
                        $current_column = self::create_column($child);
                        $current_section['elements'][] = $current_column;
                    }
                }
                // Handle widget-level elements
                else {
                    $widget = self::create_widget($child);
                    if ($widget) {
                        if ($current_column) {
                            $current_column['elements'][] = $widget;
                        } elseif ($current_section) {
                            $current_section['elements'][] = $widget;
                        } else {
                            // Create a default section if none exists
                            $current_section = self::create_default_section();
                            $current_section['elements'][] = $widget;
                            $sections[] = $current_section;
                            $current_section = null;
                        }
                    }
                }
            }
        }
        
        // Add the last section if it exists
        if ($current_section) {
            $sections[] = $current_section;
        }
        
        return empty($sections) ? [self::create_default_section()] : $sections;
    }
    
    private static function is_section_element($tagName) {
        $section_elements = ['section', 'header', 'footer', 'main', 'article', 'aside', 'nav'];
        return in_array($tagName, $section_elements);
    }
    
    private static function is_column_element($tagName) {
        $column_elements = ['div', 'span'];
        return in_array($tagName, $column_elements);
    }
    
    private static function create_section($node) {
        $section = [
            'id' => uniqid(),
            'elType' => 'section',
            'settings' => self::get_section_settings($node),
            'elements' => []
        ];
        
        // Add background if present
        $background = self::get_background_settings($node);
        if ($background) {
            $section['settings'] = array_merge($section['settings'], $background);
        }
        
        return $section;
    }
    
    private static function create_column($node) {
        $column = [
            'id' => uniqid(),
            'elType' => 'column',
            'settings' => self::get_column_settings($node),
            'elements' => []
        ];
        
        return $column;
    }
    
    private static function create_widget($node) {
        $tagName = strtolower($node->tagName);
        $widget_type = self::get_widget_type($tagName);
        
        if (!$widget_type) {
            return null;
        }
        
        $widget = [
            'id' => uniqid(),
            'elType' => 'widget',
            'widgetType' => $widget_type,
            'settings' => self::get_widget_settings($node, $widget_type)
        ];
        
        return $widget;
    }
    
    private static function create_default_section() {
        return [
            'id' => uniqid(),
            'elType' => 'section',
            'settings' => [
                'layout' => 'fullwidth',
                'content_position' => 'middle'
            ],
            'elements' => []
        ];
    }
    
    private static function get_widget_type($tagName) {
        $widget_map = [
            'h1' => 'heading',
            'h2' => 'heading',
            'h3' => 'heading',
            'h4' => 'heading',
            'h5' => 'heading',
            'h6' => 'heading',
            'p' => 'text-editor',
            'img' => 'image',
            'a' => 'button',
            'ul' => 'icon-list',
            'ol' => 'icon-list',
            'blockquote' => 'blockquote',
            'hr' => 'divider',
            'video' => 'video',
            'iframe' => 'video',
            'table' => 'table',
            'form' => 'form',
            'input' => 'text',
            'textarea' => 'textarea',
            'button' => 'button',
            'select' => 'select'
        ];
        
        return $widget_map[$tagName] ?? null;
    }
    
    private static function get_section_settings($node) {
        $settings = [
            'layout' => 'fullwidth',
            'content_position' => 'middle',
            'gap' => 'default'
        ];
        
        // Get classes for styling
        $classes = $node->getAttribute('class');
        if ($classes) {
            $settings['html_class'] = $classes;
        }
        
        // Get inline styles
        $styles = $node->getAttribute('style');
        if ($styles) {
            $style_settings = self::parse_inline_styles($styles);
            $settings = array_merge($settings, $style_settings);
        }
        
        return $settings;
    }
    
    private static function get_column_settings($node) {
        $settings = [
            '_column_size' => 100,
            '_inline_size' => null
        ];
        
        // Get classes for column width
        $classes = $node->getAttribute('class');
        if ($classes) {
            $settings['html_class'] = $classes;
            
            // Try to detect column width from classes
            if (strpos($classes, 'col-') !== false) {
                preg_match('/col-(\d+)/', $classes, $matches);
                if (isset($matches[1])) {
                    $settings['_column_size'] = intval($matches[1]) * 10; // Convert to percentage
                }
            }
        }
        
        return $settings;
    }
    
    private static function get_widget_settings($node, $widget_type) {
        $settings = [];
        
        switch ($widget_type) {
            case 'heading':
                $settings = self::get_heading_settings($node);
                break;
            case 'text-editor':
                $settings = self::get_text_editor_settings($node);
                break;
            case 'image':
                $settings = self::get_image_settings($node);
                break;
            case 'button':
                $settings = self::get_button_settings($node);
                break;
            case 'icon-list':
                $settings = self::get_icon_list_settings($node);
                break;
            case 'blockquote':
                $settings = self::get_blockquote_settings($node);
                break;
            case 'divider':
                $settings = self::get_divider_settings($node);
                break;
            default:
                $settings = self::get_generic_widget_settings($node);
        }
        
        // Add common settings
        $classes = $node->getAttribute('class');
        if ($classes) {
            $settings['html_class'] = $classes;
        }
        
        $styles = $node->getAttribute('style');
        if ($styles) {
            $style_settings = self::parse_inline_styles($styles);
            $settings = array_merge($settings, $style_settings);
        }
        
        return $settings;
    }
    
    private static function get_heading_settings($node) {
        $settings = [
            'title' => $node->textContent,
            'header_size' => self::get_heading_size($node->tagName),
            'align' => 'left'
        ];
        
        return $settings;
    }
    
    private static function get_heading_size($tagName) {
        $size_map = [
            'h1' => 'h1',
            'h2' => 'h2',
            'h3' => 'h3',
            'h4' => 'h4',
            'h5' => 'h5',
            'h6' => 'h6'
        ];
        
        return $size_map[strtolower($tagName)] ?? 'h2';
    }
    
    private static function get_text_editor_settings($node) {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }
        
        $settings = [
            'editor' => $html,
            'align' => 'left'
        ];
        
        return $settings;
    }
    
    private static function get_image_settings($node) {
        $settings = [
            'image' => [
                'url' => $node->getAttribute('src'),
                'id' => '',
                'alt' => $node->getAttribute('alt') ?: ''
            ],
            'image_size' => 'full',
            'align' => 'center',
            'caption_source' => 'none'
        ];
        
        return $settings;
    }
    
    private static function get_button_settings($node) {
        $settings = [
            'text' => $node->textContent ?: 'Click Here',
            'link' => [
                'url' => $node->getAttribute('href') ?: '#',
                'is_external' => $node->getAttribute('target') === '_blank',
                'nofollow' => strpos($node->getAttribute('rel'), 'nofollow') !== false
            ],
            'align' => 'center',
            'size' => 'md',
            'button_text_color' => '#ffffff',
            'background_color' => '#6ec1e4'
        ];
        
        return $settings;
    }
    
    private static function get_icon_list_settings($node) {
        $items = [];
        $list_items = $node->getElementsByTagName('li');
        
        foreach ($list_items as $item) {
            $items[] = [
                'text' => $item->textContent,
                'icon' => 'fas fa-check',
                'icon_color' => '#6ec1e4'
            ];
        }
        
        $settings = [
            'icon_list' => $items,
            'icon_align' => 'left'
        ];
        
        return $settings;
    }
    
    private static function get_blockquote_settings($node) {
        $settings = [
            'blockquote_content' => $node->textContent,
            'align' => 'left',
            'view' => 'traditional'
        ];
        
        return $settings;
    }
    
    private static function get_divider_settings($node) {
        $settings = [
            'style' => 'solid',
            'weight' => [
                'unit' => 'px',
                'size' => 1
            ],
            'color' => '#6ec1e4',
            'align' => 'center'
        ];
        
        return $settings;
    }
    
    private static function get_generic_widget_settings($node) {
        $settings = [
            '__dynamic__' => [
                'content' => $node->textContent
            ]
        ];
        
        return $settings;
    }
    
    private static function get_background_settings($node) {
        $background = [];
        
        // Check for background image
        $style = $node->getAttribute('style');
        if ($style && preg_match('/background-image:\s*url\(["\']([^"\']+)["\']\)/i', $style, $matches)) {
            $background['background_background'] = 'classic';
            $background['background_image'] = [
                'url' => $matches[1],
                'id' => '',
                'size' => '',
                'image' => [
                    'url' => $matches[1]
                ]
            ];
        }
        
        // Check for background color
        if ($style && preg_match('/background-color:\s*([^;]+)/i', $style, $matches)) {
            $background['background_color'] = trim($matches[1]);
        }
        
        return $background;
    }
    
    private static function parse_inline_styles($style_string) {
        $settings = [];
        $styles = explode(';', $style_string);
        
        foreach ($styles as $style) {
            $style = trim($style);
            if (empty($style)) continue;
            
            list($property, $value) = explode(':', $style, 2);
            $property = trim($property);
            $value = trim($value);
            
            switch ($property) {
                case 'text-align':
                    $settings['align'] = $value;
                    break;
                case 'color':
                    $settings['text_color'] = $value;
                    break;
                case 'background-color':
                    $settings['background_color'] = $value;
                    break;
                case 'font-size':
                    $settings['typography_typography'] = 'custom';
                    $settings['typography_font_size'] = [
                        'unit' => 'px',
                        'size' => intval($value)
                    ];
                    break;
                case 'font-weight':
                    $settings['typography_font_weight'] = $value;
                    break;
                case 'margin':
                case 'padding':
                    // Handle spacing
                    $spacing_values = explode(' ', $value);
                    if (count($spacing_values) === 1) {
                        $spacing = [
                            'unit' => 'px',
                            'top' => intval($spacing_values[0]),
                            'right' => intval($spacing_values[0]),
                            'bottom' => intval($spacing_values[0]),
                            'left' => intval($spacing_values[0])
                        ];
                    } else {
                        $spacing = [
                            'unit' => 'px',
                            'top' => intval($spacing_values[0]),
                            'right' => isset($spacing_values[1]) ? intval($spacing_values[1]) : 0,
                            'bottom' => isset($spacing_values[2]) ? intval($spacing_values[2]) : 0,
                            'left' => isset($spacing_values[3]) ? intval($spacing_values[3]) : 0
                        ];
                    }
                    $settings[$property === 'margin' ? 'margin' : 'padding'] = $spacing;
                    break;
            }
        }
        
        return $settings;
    }
}