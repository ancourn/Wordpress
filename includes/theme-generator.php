<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Theme_Generator {
    
    /**
     * Generate a WordPress theme from HTML files and assets
     * 
     * @param array $html_files Array of HTML file paths
     * @param string $assets_dir Path to the assets directory
     * @param bool $show_preview Whether to show preview after generation
     * @return string|false Theme slug or false on failure
     */
    public static function generate_theme($html_files, $assets_dir, $show_preview = false) {
        $theme_slug = "instant-theme-" . time();
        $theme_dir  = WP_CONTENT_DIR . "/themes/" . $theme_slug;
        
        // Create theme folder
        if (!file_exists($theme_dir)) {
            mkdir($theme_dir, 0755, true);
        }
        
        // 1. style.css → theme metadata
        $style_css = self::generate_style_css();
        file_put_contents("$theme_dir/style.css", $style_css);
        
        // 2. index.php → fallback template
        $index_html = self::extract_body(reset($html_files));
        $index_php  = "<?php get_header(); ?>\n" . $index_html . "\n<?php get_footer(); ?>";
        file_put_contents("$theme_dir/index.php", $index_php);
        
        // 3. header.php & footer.php (split HTML head/body)
        $first_file = reset($html_files);
        $header     = self::extract_header($first_file);
        $footer     = self::extract_footer($first_file);
        file_put_contents("$theme_dir/header.php", $header);
        file_put_contents("$theme_dir/footer.php", $footer);
        
        // 4. Copy assets (css/js/images)
        self::copy_assets($assets_dir, "$theme_dir/assets");
        
        // 5. Create additional theme files
        self::create_theme_functions($theme_dir);
        self::create_screenshot($theme_dir);
        
        // 6. Show preview if requested
        if ($show_preview) {
            self::show_theme_preview($theme_slug);
        }
        
        return $theme_slug;
    }
    
    /**
     * Generate style.css with theme metadata
     * 
     * @return string CSS content
     */
    private static function generate_style_css() {
        return "/*
Theme Name: Instant Theme " . date("Y-m-d H:i") . "
Theme URI: https://instant-wp.ai/
Author: Auto Converter
Version: 1.0
Description: Automatically generated theme from HTML import
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: instant-theme
*/";
    }
    
    /**
     * Extract body content from HTML
     * 
     * @param string $html HTML content
     * @return string Body content
     */
    private static function extract_body($html) {
        preg_match("/<body[^>]*>(.*?)<\/body>/is", $html, $matches);
        return $matches[1] ?? $html;
    }
    
    /**
     * Extract header content from HTML
     * 
     * @param string $html HTML content
     * @return string Header PHP content
     */
    private static function extract_header($html) {
        preg_match("/<head[^>]*>(.*?)<\/head>/is", $html, $matches);
        $head = $matches[1] ?? '';
        
        return "<!DOCTYPE html>\n<html <?php language_attributes(); ?>>\n<head>\n" .
            "<?php wp_head(); ?>\n" . $head . "\n</head>\n<body <?php body_class(); ?>>";
    }
    
    /**
     * Extract footer content from HTML
     * 
     * @param string $html HTML content
     * @return string Footer PHP content
     */
    private static function extract_footer($html) {
        return "<?php wp_footer(); ?>\n</body>\n</html>";
    }
    
    /**
     * Copy assets from source to destination
     * 
     * @param string $src Source directory
     * @param string $dst Destination directory
     */
    private static function copy_assets($src, $dst) {
        if (!file_exists($dst)) {
            mkdir($dst, 0755, true);
        }
        
        foreach (scandir($src) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $src_file = "$src/$file";
            $dst_file = "$dst/$file";
            
            if (is_dir($src_file)) {
                self::copy_assets($src_file, $dst_file);
            } else {
                copy($src_file, $dst_file);
            }
        }
    }
    
    /**
     * Create functions.php file with theme support
     * 
     * @param string $theme_dir Theme directory path
     */
    private static function create_theme_functions($theme_dir) {
        $functions_content = "<?php
<?php
/**
 * Instant Theme Functions
 *
 * @package Instant_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme setup
function instant_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'instant-theme'),
        'footer' => __('Footer Menu', 'instant-theme'),
    ));
}
add_action('after_setup_theme', 'instant_theme_setup');

// Enqueue styles and scripts
function instant_theme_enqueue_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style('instant-theme-style', get_stylesheet_uri());
    
    // Enqueue additional CSS files if they exist
    if (file_exists(get_template_directory() . '/assets/style.css')) {
        wp_enqueue_style('instant-theme-assets', get_template_directory_uri() . '/assets/style.css');
    }
    
    // Enqueue additional JS files if they exist
    if (file_exists(get_template_directory() . '/assets/script.js')) {
        wp_enqueue_script('instant-theme-script', get_template_directory_uri() . '/assets/script.js', array(), '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'instant_theme_enqueue_scripts');

// Customizer setup
function instant_theme_customize_register($wp_customize) {
    // Add customizer settings if needed
}
add_action('customize_register', 'instant_theme_customize_register');
";
        
        file_put_contents("$theme_dir/functions.php", $functions_content);
    }
    
    /**
     * Create a basic screenshot for the theme
     * 
     * @param string $theme_dir Theme directory path
     */
    private static function create_screenshot($theme_dir) {
        // Create a simple colored screenshot
        $screenshot_path = "$theme_dir/screenshot.png";
        
        // Create a simple 1200x900 PNG with text
        $image = imagecreatetruecolor(1200, 900);
        
        // Background color
        $bg_color = imagecolorallocate($image, 52, 152, 219); // Blue
        imagefill($image, 0, 0, $bg_color);
        
        // Text color
        $text_color = imagecolorallocate($image, 255, 255, 255); // White
        
        // Add text
        $text = "Instant Theme";
        $font_size = 60;
        $angle = 0;
        $x = 600;
        $y = 450;
        
        // Use built-in font if GD is available
        if (function_exists('imagettftext')) {
            // Try to use a system font
            $font_file = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
            if (file_exists($font_file)) {
                imagettftext($image, $font_size, $angle, $x - 200, $y, $text_color, $font_file, $text);
            } else {
                // Fallback to simple text
                imagestring($image, 5, $x - 150, $y - 20, $text, $text_color);
            }
        } else {
            // Fallback to simple text
            imagestring($image, 5, $x - 150, $y - 20, $text, $text_color);
        }
        
        // Save the image
        imagepng($image, $screenshot_path);
        imagedestroy($image);
    }
    
    /**
     * Activate the generated theme
     * 
     * @param string $theme_slug Theme slug
     * @return bool True if activated successfully
     */
    public static function activate_theme($theme_slug) {
        switch_theme($theme_slug);
        return true;
    }
    
    /**
     * Get all generated themes
     * 
     * @return array Array of theme information
     */
    public static function get_generated_themes() {
        $themes = wp_get_themes();
        $generated_themes = [];
        
        foreach ($themes as $theme_slug => $theme) {
            if (strpos($theme_slug, 'instant-theme-') === 0) {
                $generated_themes[] = [
                    'slug' => $theme_slug,
                    'name' => $theme->get('Name'),
                    'version' => $theme->get('Version'),
                    'author' => $theme->get('Author'),
                    'path' => $theme->get_theme_root(),
                    'screenshot' => $theme->get_screenshot(),
                ];
            }
        }
        
        return $generated_themes;
    }
    
    /**
     * Delete a generated theme
     * 
     * @param string $theme_slug Theme slug
     * @return bool True if deleted successfully
     */
    public static function delete_theme($theme_slug) {
        if (strpos($theme_slug, 'instant-theme-') !== 0) {
            return false; // Only delete instant themes
        }
        
        $theme = wp_get_theme($theme_slug);
        if (!$theme->exists()) {
            return false;
        }
        
        // Don't delete the currently active theme
        if (get_stylesheet() === $theme_slug) {
            return false;
        }
        
        // Delete the theme
        delete_theme($theme_slug);
        return true;
    }
    
    /**
     * Generate a theme from imported pages
     * 
     * @param array $pages Array of page data
     * @param string $assets_dir Path to assets directory
     * @return string|false Theme slug or false on failure
     */
    public static function generate_theme_from_pages($pages, $assets_dir) {
        if (empty($pages)) {
            return false;
        }
        
        // Extract HTML content from pages
        $html_files = [];
        foreach ($pages as $page) {
            $post = get_post($page['page_id']);
            if ($post) {
                // Reconstruct HTML from post content
                $html = self::reconstruct_html($post);
                $html_files[] = $html;
            }
        }
        
        return self::generate_theme($html_files, $assets_dir);
    }
    
    /**
     * Reconstruct HTML from post content
     * 
     * @param WP_Post $post Post object
     * @return string Reconstructed HTML
     */
    private static function reconstruct_html($post) {
        $title = get_the_title($post);
        $content = apply_filters('the_content', $post->post_content);
        
        // Basic HTML structure
        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<meta charset=\"" . get_bloginfo('charset') . "\">\n";
        $html .= "<title>" . $title . " - " . get_bloginfo('name') . "</title>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>" . $title . "</h1>\n";
        $html .= $content;
        $html .= "\n</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Show theme preview after generation
     * 
     * @param string $theme_slug The theme slug
     */
    private static function show_theme_preview($theme_slug) {
        // Check if live preview class is available
        if (class_exists('HTML_WP_Live_Preview')) {
            HTML_WP_Live_Preview::show_preview($theme_slug);
        }
    }
    
    /**
     * Generate theme with preview enabled
     * 
     * @param array $html_files Array of HTML file paths
     * @param string $assets_dir Path to the assets directory
     * @return string|false Theme slug or false on failure
     */
    public static function generate_theme_with_preview($html_files, $assets_dir) {
        return self::generate_theme($html_files, $assets_dir, true);
    }
}