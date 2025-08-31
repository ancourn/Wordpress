<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class HTML_WP_Elementor_Mapper {
    public static function html_to_elementor_json($html) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        $body = $dom->getElementsByTagName("body")->item(0);
        if (!$body) return [];
        $elements = [];
        foreach ($body->childNodes as $node) {
            $widget = self::map_node_to_elementor($node);
            if ($widget) {
                $elements[] = $widget;
            }
        }
        return [[
            'id'       => uniqid(),
            'elType'   => 'section',
            'settings' => [],
            'elements' => [[
                'id'       => uniqid(),
                'elType'   => 'column',
                'settings' => [],
                'elements' => $elements
            ]]
        ]];
    }
    private static function map_node_to_elementor($node) {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->textContent);
            if ($text === '') return null;
            return self::make_widget('text-editor', ['editor' => $text]);
        }
        if ($node->nodeType !== XML_ELEMENT_NODE) return null;
        switch (strtolower($node->nodeName)) {
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                return self::make_widget('heading', [
                    'title' => $node->textContent,
                    'header_size' => strtoupper($node->nodeName)
                ]);
            case 'p':
                return self::make_widget('text-editor', ['editor' => $node->textContent]);
            case 'img':
                return self::make_widget('image', [
                    'image' => [
                        'url' => $node->getAttribute('src')
                    ]
                ]);
            case 'a':
                return self::make_widget('button', [
                    'text' => $node->textContent,
                    'link' => ['url' => $node->getAttribute('href')]
                ]);
            case 'ul':
            case 'ol':
                $items = [];
                foreach ($node->getElementsByTagName("li") as $li) {
                    $items[] = $li->textContent;
                }
                return self::make_widget('text-editor', ['editor' => implode("<br>", $items)]);
            default:
                // fallback: raw HTML
                return self::make_widget('html', ['html' => $dom->saveHTML($node)]);
        }
    }
    private static function make_widget($type, $settings) {
        return [
            'id'        => uniqid(),
            'elType'    => 'widget',
            'widgetType'=> $type,
            'settings'  => $settings,
            'elements'  => []
        ];
    }
}