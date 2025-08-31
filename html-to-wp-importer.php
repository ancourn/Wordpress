<?php
/*
Plugin Name: HTML to WordPress Importer
Plugin URI: https://yourdomain.com/
Description: Import static HTML/ZIP websites into WordPress as pages with assets.
Version: 0.1
Author: Your Team
License: GPL2
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include required files
require_once plugin_dir_path( __FILE__ ) . 'includes/parser.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/assets.php';

class HTML_To_WP_Importer {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_html_import_upload', array($this, 'handle_upload'));
        
        // Create upload directory on plugin activation
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function activate() {
        $upload_dir = wp_upload_dir();
        $asset_dir = $upload_dir['basedir'] . '/html-importer';
        
        if (!file_exists($asset_dir)) {
            wp_mkdir_p($asset_dir);
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'HTML Importer',
            'HTML Importer',
            'manage_options',
            'html-to-wp-importer',
            array($this, 'admin_page'),
            'dashicons-upload',
            100
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>HTML to WordPress Importer</h1>
            <p>Upload HTML files or ZIP archives to convert them into WordPress pages with proper asset handling.</p>
            
            <?php
            // Display success/error messages
            if (isset($_GET['import_success'])) {
                $count = intval($_GET['import_success']);
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($count . ' pages imported successfully!') . '</p></div>';
            }
            
            if (isset($_GET['import_error'])) {
                $error = sanitize_text_field($_GET['import_error']);
                echo '<div class="notice notice-error is-dismissible"><p>Error: ' . esc_html($error) . '</p></div>';
            }
            ?>
            
            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="html_import_upload">
                <?php wp_nonce_field('html_import_upload_nonce', 'html_import_upload_nonce_field'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="html_file">Select HTML file or ZIP archive:</label>
                        </th>
                        <td>
                            <input type="file" name="html_file" id="html_file" accept=".html,.zip" required>
                            <p class="description">Supported formats: .html, .zip</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="parent_page">Parent Page (optional):</label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'name' => 'parent_page',
                                'id' => 'parent_page',
                                'show_option_none' => '— No Parent —',
                                'option_none_value' => '0',
                            ));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label>Options:</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="create_menu" value="1" checked>
                                Create navigation menu from imported pages
                            </label><br>
                            <label>
                                <input type="checkbox" name="overwrite_existing" value="1">
                                Overwrite existing pages with same title
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Upload & Import', 'primary'); ?>
            </form>
            
            <div class="card">
                <h3>How it works:</h3>
                <ul>
                    <li>Upload a single HTML file or a ZIP archive containing multiple HTML files</li>
                    <li>The plugin will extract the title from &lt;title&gt; tag and content from &lt;body&gt; tag</li>
                    <li>All assets (CSS, JS, images) will be copied to the uploads folder</li>
                    <li>Links in HTML will be rewritten to point to the new asset locations</li>
                    <li>Each HTML file will become a published WordPress page</li>
                    <li>A navigation menu will be created linking all imported pages</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public function handle_upload() {
        // Verify nonce
        if (!isset($_POST['html_import_upload_nonce_field']) || !wp_verify_nonce($_POST['html_import_upload_nonce_field'], 'html_import_upload_nonce')) {
            wp_redirect(admin_url('admin.php?page=html-to-wp-importer&import_error=' . urlencode('Security check failed.')));
            exit;
        }
        
        if (!isset($_FILES['html_file'])) {
            wp_redirect(admin_url('admin.php?page=html-to-wp-importer&import_error=' . urlencode('No file uploaded.')));
            exit;
        }
        
        try {
            // Process the uploaded file
            $created_pages = HTML_WP_Parser::process($_FILES['html_file']);
            
            // Set parent page if specified
            if (!empty($created_pages) && isset($_POST['parent_page']) && intval($_POST['parent_page']) > 0) {
                $parent_page_id = intval($_POST['parent_page']);
                foreach ($created_pages as $page_id) {
                    wp_update_post(array(
                        'ID' => $page_id,
                        'post_parent' => $parent_page_id
                    ));
                }
            }
            
            wp_redirect(admin_url('admin.php?page=html-to-wp-importer&import_success=' . count($created_pages)));
            exit;
            
        } catch (Exception $e) {
            wp_redirect(admin_url('admin.php?page=html-to-wp-importer&import_error=' . urlencode($e->getMessage())));
            exit;
        }
    }
}

new HTML_To_WP_Importer();