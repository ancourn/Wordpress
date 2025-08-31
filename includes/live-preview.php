<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Live_Preview {
    
    /**
     * Show preview notification for generated theme
     * 
     * @param string $theme_slug The theme slug
     * @param bool $show_activation_link Whether to show activation link
     */
    public static function show_preview($theme_slug, $show_activation_link = true) {
        $preview_url = admin_url("customize.php?theme=" . $theme_slug);
        $activate_url = admin_url("themes.php?action=activate&stylesheet=" . urlencode($theme_slug));
        $theme_info = wp_get_theme($theme_slug);
        
        echo '<div class="notice notice-success is-dismissible html-importer-preview-notice">';
        echo '<h4><strong>🎨 Theme Generated Successfully!</strong></h4>';
        
        if ($theme_info && $theme_info->exists()) {
            echo '<p><strong>Theme Name:</strong> ' . esc_html($theme_info->get('Name')) . '</p>';
            echo '<p><strong>Version:</strong> ' . esc_html($theme_info->get('Version')) . '</p>';
        }
        
        echo '<div class="html-importer-preview-actions">';
        echo '<a href="' . esc_url($preview_url) . '" class="button button-primary" target="_blank" rel="noopener noreferrer">';
        echo '🔍 Preview Theme';
        echo '</a>';
        
        if ($show_activation_link) {
            echo ' <a href="' . esc_url($activate_url) . '" class="button" onclick="return confirm(\'Are you sure you want to activate this theme?\');">';
            echo '✨ Activate Theme';
            echo '</a>';
        }
        
        echo ' <a href="' . esc_url(admin_url('themes.php')) . '" class="button">';
        echo '📋 View All Themes';
        echo '</a>';
        echo '</div>';
        
        echo '<p><em>💡 Tip: Use the preview to customize your theme before activating it. You can change colors, fonts, and layout options.</em></p>';
        echo '</div>';
        
        // Add some styling for the preview notice
        self::add_preview_styles();
    }
    
    /**
     * Show preview in a modal overlay
     * 
     * @param string $theme_slug The theme slug
     */
    public static function show_modal_preview($theme_slug) {
        $preview_url = admin_url("customize.php?theme=" . $theme_slug);
        $theme_info = wp_get_theme($theme_slug);
        
        echo '<div id="html-importer-preview-modal" class="html-importer-modal" style="display: none;">';
        echo '<div class="html-importer-modal-content">';
        echo '<div class="html-importer-modal-header">';
        echo '<h3>🎨 Theme Preview</h3>';
        echo '<span class="html-importer-modal-close">&times;</span>';
        echo '</div>';
        echo '<div class="html-importer-modal-body">';
        
        if ($theme_info && $theme_info->exists()) {
            echo '<div class="theme-info">';
            echo '<h4>' . esc_html($theme_info->get('Name')) . '</h4>';
            echo '<p>Version: ' . esc_html($theme_info->get('Version')) . '</p>';
            echo '<p>Author: ' . esc_html($theme_info->get('Author')) . '</p>';
            echo '</div>';
        }
        
        echo '<div class="preview-actions">';
        echo '<a href="' . esc_url($preview_url) . '" class="button button-primary" target="_blank" rel="noopener noreferrer">';
        echo '🔍 Open in Customizer';
        echo '</a>';
        echo '<button class="button html-importer-preview-iframe" data-theme="' . esc_attr($theme_slug) . '">';
        echo '👁️ Quick Preview';
        echo '</button>';
        echo '</div>';
        
        echo '<div class="preview-iframe-container" style="display: none;">';
        echo '<iframe id="html-importer-preview-iframe" src="" width="100%" height="600px" frameborder="0"></iframe>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Add modal styles and scripts
        self::add_modal_styles();
        self::add_modal_scripts();
    }
    
    /**
     * Get preview URL for a theme
     * 
     * @param string $theme_slug The theme slug
     * @return string Preview URL
     */
    public static function get_preview_url($theme_slug) {
        return admin_url("customize.php?theme=" . $theme_slug);
    }
    
    /**
     * Get activation URL for a theme
     * 
     * @param string $theme_slug The theme slug
     * @return string Activation URL
     */
    public static function get_activation_url($theme_slug) {
        return admin_url("themes.php?action=activate&stylesheet=" . urlencode($theme_slug));
    }
    
    /**
     * Check if theme preview is available
     * 
     * @param string $theme_slug The theme slug
     * @return bool True if preview is available
     */
    public static function is_preview_available($theme_slug) {
        $theme = wp_get_theme($theme_slug);
        return $theme && $theme->exists();
    }
    
    /**
     * Show preview options in import results
     * 
     * @param array $results Import results
     */
    public static function show_import_preview_options($results) {
        if (isset($results['theme_generated']) && $results['theme_generated'] && isset($results['theme_slug'])) {
            $theme_slug = $results['theme_slug'];
            
            if (self::is_preview_available($theme_slug)) {
                echo '<div class="html-importer-preview-section">';
                echo '<h4>🎨 Theme Generated</h4>';
                echo '<p>Your WordPress theme has been created successfully!</p>';
                
                echo '<div class="preview-options">';
                echo '<a href="' . esc_url(self::get_preview_url($theme_slug)) . '" class="button button-primary" target="_blank" rel="noopener noreferrer">';
                echo '🔍 Preview Theme';
                echo '</a>';
                
                echo '<a href="' . esc_url(self::get_activation_url($theme_slug)) . '" class="button" onclick="return confirm(\'Are you sure you want to activate this theme?\');">';
                echo '✨ Activate Theme';
                echo '</a>';
                
                echo '<button class="button html-importer-show-modal-preview" data-theme="' . esc_attr($theme_slug) . '">';
                echo '👁️ Quick Preview';
                echo '</button>';
                echo '</div>';
                
                echo '</div>';
                
                // Add modal for quick preview
                self::show_modal_preview($theme_slug);
            } else {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>Theme generation completed, but the theme is not available for preview.</strong></p>';
                echo '<p>Please check the themes directory to ensure the theme was created correctly.</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Add custom styles for preview notices
     */
    private static function add_preview_styles() {
        if (!wp_style_is('html-importer-preview', 'enqueued')) {
            ?>
            <style>
                .html-importer-preview-notice {
                    border-left-color: #00a0d2;
                    padding: 15px;
                    margin: 15px 0;
                }
                .html-importer-preview-actions {
                    margin: 10px 0;
                }
                .html-importer-preview-actions .button {
                    margin-right: 10px;
                    margin-bottom: 5px;
                }
                .html-importer-preview-section {
                    background: #f9f9f9;
                    border: 1px solid #e5e5e5;
                    padding: 15px;
                    margin: 15px 0;
                    border-radius: 4px;
                }
                .html-importer-preview-section h4 {
                    margin: 0 0 10px 0;
                    color: #23282d;
                }
                .preview-options {
                    margin: 15px 0;
                }
                .preview-options .button {
                    margin-right: 10px;
                    margin-bottom: 5px;
                }
            </style>
            <?php
        }
    }
    
    /**
     * Add modal styles
     */
    private static function add_modal_styles() {
        if (!wp_style_is('html-importer-modal', 'enqueued')) {
            ?>
            <style>
                .html-importer-modal {
                    display: none;
                    position: fixed;
                    z-index: 100000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.5);
                }
                .html-importer-modal-content {
                    background-color: #fefefe;
                    margin: 5% auto;
                    padding: 0;
                    border: 1px solid #888;
                    width: 90%;
                    max-width: 800px;
                    border-radius: 4px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .html-importer-modal-header {
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-bottom: 1px solid #dee2e6;
                    border-radius: 4px 4px 0 0;
                }
                .html-importer-modal-header h3 {
                    margin: 0;
                    color: #23282d;
                }
                .html-importer-modal-close {
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                    line-height: 1;
                }
                .html-importer-modal-close:hover {
                    color: #000;
                }
                .html-importer-modal-body {
                    padding: 20px;
                }
                .theme-info {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 4px;
                    margin-bottom: 15px;
                }
                .theme-info h4 {
                    margin: 0 0 10px 0;
                    color: #23282d;
                }
                .preview-actions {
                    margin-bottom: 15px;
                }
                .preview-actions .button {
                    margin-right: 10px;
                }
                .preview-iframe-container {
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    overflow: hidden;
                }
            </style>
            <?php
        }
    }
    
    /**
     * Add modal scripts
     */
    private static function add_modal_scripts() {
        if (!wp_script_is('html-importer-modal', 'enqueued')) {
            ?>
            <script>
                jQuery(document).ready(function($) {
                    // Modal functionality
                    $('.html-importer-show-modal-preview').on('click', function() {
                        var theme = $(this).data('theme');
                        var modal = $('#html-importer-preview-modal');
                        var iframe = $('#html-importer-preview-iframe');
                        
                        // Set iframe src to theme preview
                        iframe.attr('src', '<?php echo home_url(); ?>?theme=' + encodeURIComponent(theme));
                        
                        modal.show();
                    });
                    
                    // Close modal
                    $('.html-importer-modal-close').on('click', function() {
                        $('#html-importer-preview-modal').hide();
                    });
                    
                    // Close modal when clicking outside
                    $(window).on('click', function(event) {
                        var modal = $('#html-importer-preview-modal');
                        if (event.target == modal[0]) {
                            modal.hide();
                        }
                    });
                    
                    // Toggle iframe container
                    $('.html-importer-preview-iframe').on('click', function() {
                        var container = $('.preview-iframe-container');
                        container.toggle();
                    });
                });
            </script>
            <?php
        }
    }
    
    /**
     * Add preview functionality to admin notices
     */
    public static function add_admin_notice_preview() {
        // Check if we have a theme to preview
        if (isset($_GET['theme_preview']) && !empty($_GET['theme_preview'])) {
            $theme_slug = sanitize_text_field($_GET['theme_preview']);
            if (self::is_preview_available($theme_slug)) {
                self::show_preview($theme_slug);
            }
        }
    }
    
    /**
     * Initialize the live preview functionality
     */
    public static function init() {
        add_action('admin_notices', array(__CLASS__, 'add_admin_notice_preview'));
    }
}

// Initialize the live preview functionality
HTML_WP_Live_Preview::init();