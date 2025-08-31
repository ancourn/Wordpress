<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Theme_Generator {
    
    public static function generate_theme_from_html($html_content, $theme_name = 'Generated Theme') {
        $theme_data = self::extract_theme_data($html_content);
        
        if (empty($theme_data)) {
            return false;
        }
        
        return self::create_theme_files($theme_data, $theme_name);
    }
    
    private static function extract_theme_data($html_content) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $theme_data = [
            'name' => 'Generated Theme',
            'description' => 'Theme generated from HTML import',
            'version' => '1.0.0',
            'author' => 'HTML to WordPress Importer',
            'templates' => [],
            'styles' => '',
            'scripts' => ''
        ];
        
        // Extract head elements
        $head = $dom->getElementsByTagName('head')->item(0);
        if ($head) {
            self::extract_head_elements($head, $theme_data);
        }
        
        // Extract body structure
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            self::extract_body_structure($body, $theme_data);
        }
        
        return $theme_data;
    }
    
    private static function extract_head_elements($head, &$theme_data) {
        // Extract title
        $title = $head->getElementsByTagName('title')->item(0);
        if ($title) {
            $theme_data['name'] = $title->textContent;
        }
        
        // Extract meta tags
        $meta_tags = $head->getElementsByTagName('meta');
        foreach ($meta_tags as $meta) {
            $name = $meta->getAttribute('name');
            $content = $meta->getAttribute('content');
            
            switch ($name) {
                case 'description':
                    $theme_data['description'] = $content;
                    break;
                case 'author':
                    $theme_data['author'] = $content;
                    break;
                case 'viewport':
                    $theme_data['viewport'] = $content;
                    break;
            }
        }
        
        // Extract CSS links
        $link_tags = $head->getElementsByTagName('link');
        foreach ($link_tags as $link) {
            $rel = $link->getAttribute('rel');
            $href = $link->getAttribute('href');
            
            if ($rel === 'stylesheet') {
                $theme_data['stylesheets'][] = $href;
            }
        }
        
        // Extract style tags
        $style_tags = $head->getElementsByTagName('style');
        foreach ($style_tags as $style) {
            $theme_data['styles'] .= $style->textContent . "\n";
        }
        
        // Extract script tags
        $script_tags = $head->getElementsByTagName('script');
        foreach ($script_tags as $script) {
            if ($script->getAttribute('src')) {
                $theme_data['scripts'][] = $script->getAttribute('src');
            } else {
                $theme_data['scripts'] .= $script->textContent . "\n";
            }
        }
    }
    
    private static function extract_body_structure($body, &$theme_data) {
        // Extract header
        $header = $body->getElementsByTagName('header')->item(0);
        if ($header) {
            $theme_data['templates']['header'] = $body->ownerDocument->saveHTML($header);
        }
        
        // Extract footer
        $footer = $body->getElementsByTagName('footer')->item(0);
        if ($footer) {
            $theme_data['templates']['footer'] = $body->ownerDocument->saveHTML($footer);
        }
        
        // Extract navigation
        $nav = $body->getElementsByTagName('nav')->item(0);
        if ($nav) {
            $theme_data['templates']['navigation'] = $body->ownerDocument->saveHTML($nav);
        }
        
        // Extract main content area
        $main = $body->getElementsByTagName('main')->item(0);
        if ($main) {
            $theme_data['templates']['main'] = $body->ownerDocument->saveHTML($main);
        }
        
        // Extract sidebar
        $aside = $body->getElementsByTagName('aside')->item(0);
        if ($aside) {
            $theme_data['templates']['sidebar'] = $body->ownerDocument->saveHTML($aside);
        }
    }
    
    private static function create_theme_files($theme_data, $theme_name) {
        $themes_dir = WP_CONTENT_DIR . '/themes';
        $theme_slug = sanitize_title($theme_name);
        $theme_path = $themes_dir . '/' . $theme_slug;
        
        // Create theme directory
        if (!file_exists($theme_path)) {
            wp_mkdir_p($theme_path);
        }
        
        // Create style.css
        $style_css = self::generate_style_css($theme_data);
        file_put_contents($theme_path . '/style.css', $style_css);
        
        // Create functions.php
        $functions_php = self::generate_functions_php($theme_data);
        file_put_contents($theme_path . '/functions.php', $functions_php);
        
        // Create template files
        if (isset($theme_data['templates']['header'])) {
            $header_php = self::generate_header_php($theme_data['templates']['header']);
            file_put_contents($theme_path . '/header.php', $header_php);
        }
        
        if (isset($theme_data['templates']['footer'])) {
            $footer_php = self::generate_footer_php($theme_data['templates']['footer']);
            file_put_contents($theme_path . '/footer.php', $footer_php);
        }
        
        if (isset($theme_data['templates']['navigation'])) {
            $nav_php = self::generate_nav_php($theme_data['templates']['navigation']);
            file_put_contents($theme_path . '/navigation.php', $nav_php);
        }
        
        // Create index.php
        $index_php = self::generate_index_php($theme_data);
        file_put_contents($theme_path . '/index.php', $index_php);
        
        // Create page.php
        $page_php = self::generate_page_php($theme_data);
        file_put_contents($theme_path . '/page.php', $page_php);
        
        // Create screenshot.png (placeholder)
        self::create_screenshot($theme_path);
        
        return $theme_path;
    }
    
    private static function generate_style_css($theme_data) {
        return "/*\nTheme Name: {$theme_data['name']}\n" .
               "Theme URI: " . get_site_url() . "\n" .
               "Description: {$theme_data['description']}\n" .
               "Version: {$theme_data['version']}\n" .
               "Author: {$theme_data['author']}\n" .
               "Text Domain: " . sanitize_title($theme_data['name']) . "\n" .
               "*/\n\n" .
               $theme_data['styles'];
    }
    
    private static function generate_functions_php($theme_data) {
        return "<?php\n" .
               "if (!defined('ABSPATH')) exit;\n\n" .
               "// Theme setup\n" .
               "function " . sanitize_title($theme_data['name']) . "_setup() {\n" .
               "    // Add theme support\n" .
               "    add_theme_support('title-tag');\n" .
               "    add_theme_support('post-thumbnails');\n" .
               "    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));\n" .
               "    add_theme_support('customize-selective-refresh-widgets');\n\n" .
               "    // Register navigation menus\n" .
               "    register_nav_menus(array(\n" .
               "        'primary' => __('Primary Menu', '" . sanitize_title($theme_data['name']) . "'),\n" .
               "        'footer' => __('Footer Menu', '" . sanitize_title($theme_data['name']) . "')\n" .
               "    ));\n" .
               "}\n" .
               "add_action('after_setup_theme', '" . sanitize_title($theme_data['name']) . "_setup');\n\n" .
               "// Enqueue scripts and styles\n" .
               "function " . sanitize_title($theme_data['name']) . "_scripts() {\n" .
               "    wp_enqueue_style('" . sanitize_title($theme_data['name']) . "-style', get_stylesheet_uri());\n" .
               "    wp_enqueue_script('" . sanitize_title($theme_data['name']) . "-script', get_template_directory_uri() . '/js/script.js', array('jquery'), '1.0.0', true);\n" .
               "}\n" .
               "add_action('wp_enqueue_scripts', '" . sanitize_title($theme_data['name']) . "_scripts');\n" .
               "?>";
    }
    
    private static function generate_header_php($header_html) {
        return "<?php\n" .
               "if (!defined('ABSPATH')) exit;\n" .
               "<!DOCTYPE html>\n" .
               "<html <?php language_attributes(); ?>>\n" .
               "<head>\n" .
               "    <meta charset=\"<?php bloginfo('charset'); ?>\">\n" .
               "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n" .
               "    <?php wp_head(); ?>\n" .
               "</head>\n" .
               "<body <?php body_class(); ?>>\n" .
               "    <?php wp_body_open(); ?>\n" .
               "    " . $header_html . "\n";
    }
    
    private static function generate_footer_php($footer_html) {
        return "<?php\n" .
               "if (!defined('ABSPATH')) exit;\n" .
               "    " . $footer_html . "\n" .
               "    <?php wp_footer(); ?>\n" .
               "</body>\n" .
               "</html>";
    }
    
    private static function generate_nav_php($nav_html) {
        return "<?php\n" .
               "if (!defined('ABSPATH')) exit;\n" .
               "    " . $nav_html . "\n";
    }
    
    private static function generate_index_php($theme_data) {
        return "<?php\n" .
               "if (!defined('ABSPATH')) exit;\n" .
               "get_header(); ?>\n\n" .
               "<div id=\"primary\" class=\"content-area\">\n" .
               "    <main id=\"main\" class=\"site-main\">\n\n" .
               "        <?php if (have_posts()) : ?>\n" .
               "            <?php while (have_posts()) : the_post(); ?>\n" .
               "                <?php get_template_part('template-parts/content', get_post_type()); ?>\n" .
               "            <?php endwhile; ?>\n\n" .
               "            <?php the_posts_navigation(); ?>\n" .
               "        <?php else : ?>\n" .
               "            <?php get_template_part('template-parts/content', 'none'); ?>\n" .
               "        <?php endif; ?>\n\n" .
               "    </main><!-- #main -->\n" .
               "</div><!-- #primary -->\n\n" .
               "<?php get_sidebar(); ?>\n" .
               "<?php get_footer(); ?>";
    }
    
    private static function generate_page_php($theme_data) {
        return "<?php\n" .
               "if (!defined('ABSPATH')) exit;\n" .
               "get_header(); ?>\n\n" .
               "<div id=\"primary\" class=\"content-area\">\n" .
               "    <main id=\"main\" class=\"site-main\">\n\n" .
               "        <?php while (have_posts()) : the_post(); ?>\n\n" .
               "            <?php get_template_part('template-parts/content', 'page'); ?>\n\n" .
               "            <?php if (comments_open() || get_comments_number()) : ?>\n" .
               "                <?php comments_template(); ?>\n" .
               "            <?php endif; ?>\n\n" .
               "        <?php endwhile; ?>\n\n" .
               "    </main><!-- #main -->\n" .
               "</div><!-- #primary -->\n\n" .
               "<?php get_sidebar(); ?>\n" .
               "<?php get_footer(); ?>";
    }
    
    private static function create_screenshot($theme_path) {
        // Create a simple 1200x900 screenshot
        $image = imagecreatetruecolor(1200, 900);
        $bg_color = imagecolorallocate($image, 240, 240, 240);
        $text_color = imagecolorallocate($image, 100, 100, 100);
        
        imagefill($image, 0, 0, $bg_color);
        
        // Add text
        $text = 'Generated Theme';
        $font_size = 48;
        $x = 600;
        $y = 450;
        
        // Use built-in font
        imagestring($image, 5, $x - 150, $y - 20, $text, $text_color);
        
        imagepng($image, $theme_path . '/screenshot.png');
        imagedestroy($image);
    }
    
    public static function activate_theme($theme_name) {
        $theme_slug = sanitize_title($theme_name);
        $theme = wp_get_theme($theme_slug);
        
        if ($theme->exists()) {
            switch_theme($theme_slug);
            return true;
        }
        
        return false;
    }
}