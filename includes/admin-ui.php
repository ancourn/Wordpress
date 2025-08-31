<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HTML_WP_Admin_UI {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_post_html_import_upload', array($this, 'handle_single_upload'));
        add_action('admin_post_html_zip_import', array($this, 'handle_zip_import'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'HTML Importer',
            'HTML Importer',
            'manage_options',
            'html-importer',
            array($this, 'render_admin_page'),
            'dashicons-upload',
            100
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_html-importer') {
            return;
        }
        
        // Enqueue WordPress admin scripts
        wp_enqueue_style('wp-admin');
        wp_enqueue_style('buttons');
        wp_enqueue_style('forms');
        
        // Enqueue plugin styles
        wp_enqueue_style('html-importer-admin', plugins_url('assets/css/admin.css', dirname(__FILE__)), array(), '0.1.0');
        
        // Enqueue plugin scripts
        wp_enqueue_script('html-importer-admin', plugins_url('assets/js/admin.js', dirname(__FILE__)), array('jquery'), '0.1.0', true);
        
        // Localize script
        wp_localize_script('html-importer-admin', 'htmlImporter', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('html_importer_nonce'),
            'messages' => array(
                'uploading' => 'Uploading and processing...',
                'processing' => 'Processing files...',
                'complete' => 'Import complete!',
                'error' => 'An error occurred during import.'
            )
        ));
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap html-importer-admin">
            <h1>HTML to WordPress Importer</h1>
            <p class="description">Upload HTML files or ZIP archives to convert them into WordPress pages with proper asset handling.</p>
            
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
            
            // Display ZIP import results if available
            if (isset($_GET['zip_import_results'])) {
                $results = get_transient('html_importer_results');
                if ($results) {
                    $this->display_import_results($results);
                    delete_transient('html_importer_results');
                }
            }
            ?>
            
            <div class="html-importer-tabs">
                <ul class="tab-nav">
                    <li class="tab active" data-tab="single-file">Single File</li>
                    <li class="tab" data-tab="zip-file">ZIP Archive</li>
                </ul>
                
                <div class="tab-content">
                    <!-- Single File Upload Tab -->
                    <div id="single-file" class="tab-pane active">
                        <div class="card">
                            <h2>Upload Single HTML File</h2>
                            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                                <input type="hidden" name="action" value="html_import_upload">
                                <?php wp_nonce_field('html_import_upload_nonce', 'html_import_upload_nonce_field'); ?>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="html_file">Select HTML file:</label>
                                        </th>
                                        <td>
                                            <input type="file" name="html_file" id="html_file" accept=".html,.htm" required>
                                            <p class="description">Supported formats: .html, .htm</p>
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
                                            </label><br>
                                            <?php if (HTML_WP_Elementor::is_elementor_active()): ?>
                                            <label>
                                                <input type="checkbox" name="use_elementor" value="1">
                                                Convert to Elementor blocks (requires Elementor plugin)
                                            </label>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                                
                                <?php submit_button('Upload & Import', 'primary'); ?>
                            </form>
                        </div>
                    </div>
                    
                    <!-- ZIP File Upload Tab -->
                    <div id="zip-file" class="tab-pane">
                        <div class="card">
                            <h2>Upload ZIP Archive</h2>
                            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                                <input type="hidden" name="action" value="html_zip_import">
                                <?php wp_nonce_field('html_zip_import_nonce', 'html_zip_import_nonce_field'); ?>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="html_zip">Select ZIP archive:</label>
                                        </th>
                                        <td>
                                            <input type="file" name="html_zip" id="html_zip" accept=".zip" required>
                                            <p class="description">Upload a ZIP file containing HTML files and assets</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="parent_page_zip">Parent Page (optional):</label>
                                        </th>
                                        <td>
                                            <?php
                                            wp_dropdown_pages(array(
                                                'name' => 'parent_page',
                                                'id' => 'parent_page_zip',
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
                                            </label><br>
                                            <?php if (HTML_WP_Elementor::is_elementor_active()): ?>
                                            <label>
                                                <input type="checkbox" name="use_elementor" value="1">
                                                Convert to Elementor blocks (requires Elementor plugin)
                                            </label>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                                
                                <?php submit_button('Upload & Import ZIP', 'primary'); ?>
                            </form>
                        </div>
                        
                        <div class="card">
                            <h3>Advanced Options</h3>
                            <p><strong>Image Processing:</strong> External images will be downloaded and converted to WordPress attachments for better performance and reliability.</p>
                            <p><strong>Form Conversion:</strong> HTML forms can be converted to Contact Form 7 forms for better WordPress integration.</p>
                            <p><strong>Background Processing:</strong> Large ZIP files can be processed in the background to avoid timeout issues.</p>
                            <p><strong>Note:</strong> These features are optional and can be enabled in the import options above.</p>
                        </div>
                        
                        <div class="card">
                            <h3>ZIP Archive Requirements</h3>
                            <ul>
                                <li>ZIP file should contain HTML files (.html or .htm)</li>
                                <li>Assets (CSS, JS, images) will be automatically processed</li>
                                <li>HTML files can be in root directory or subdirectories</li>
                                <li>Page titles will be extracted from HTML title tags or filenames</li>
                                <li>Navigation menu will be created automatically (if selected)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3>How it works:</h3>
                <ul>
                    <li>Upload a single HTML file or a ZIP archive containing multiple HTML files</li>
                    <li>The plugin will extract the title from <title> tag and content from <body> tag</li>
                    <li>All assets (CSS, JS, images) will be copied to the uploads folder</li>
                    <li>Links in HTML will be rewritten to point to the new asset locations</li>
                    <li>Each HTML file will become a published WordPress page</li>
                    <li>A navigation menu will be created linking all imported pages</li>
                    <?php if (HTML_WP_Elementor::is_elementor_active()): ?>
                    <li>If Elementor conversion is selected, content will be converted to Elementor blocks</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public function handle_single_upload() {
        // Verify nonce
        if (!isset($_POST['html_import_upload_nonce_field']) || !wp_verify_nonce($_POST['html_import_upload_nonce_field'], 'html_import_upload_nonce')) {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode('Security check failed.')));
            exit;
        }
        
        if (!isset($_FILES['html_file'])) {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode('No file uploaded.')));
            exit;
        }
        
        // Check file type
        $file_info = wp_check_filetype_and_ext($_FILES['html_file']['tmp_name'], $_FILES['html_file']['name']);
        if (!in_array(strtolower($file_info['ext']), ['html', 'htm'])) {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode('Invalid file type. Please upload an HTML file.')));
            exit;
        }
        
        // Check if Elementor option is selected but Elementor is not active
        $use_elementor = isset($_POST['use_elementor']) && $_POST['use_elementor'] == '1';
        if ($use_elementor && !HTML_WP_Elementor::is_elementor_active()) {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode('Elementor plugin is not active. Please install and activate Elementor first.')));
            exit;
        }
        
        $parent_page = isset($_POST['parent_page']) ? intval($_POST['parent_page']) : 0;
        $create_menu = isset($_POST['create_menu']) && $_POST['create_menu'] == '1';
        
        try {
            // Process the uploaded file using the parser
            // The parser now handles menu building automatically using the new approach
            $created_pages = HTML_WP_Parser::process($_FILES['html_file'], $use_elementor);
            
            // Set parent page if specified
            if (!empty($created_pages) && $parent_page > 0) {
                foreach ($created_pages as $page_id) {
                    wp_update_post(array(
                        'ID' => $page_id,
                        'post_parent' => $parent_page
                    ));
                }
            }
            
            // Note: Menu building is now handled automatically by the parser
            // using the new HTML_WP_Menu_Builder and HTML_WP_Link_Mapper
            
            wp_redirect(admin_url('admin.php?page=html-importer&import_success=' . count($created_pages)));
            exit;
            
        } catch (Exception $e) {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode($e->getMessage())));
            exit;
        }
    }
    
    public function handle_zip_import() {
        // Verify nonce
        if (!isset($_POST['html_zip_import_nonce_field']) || !wp_verify_nonce($_POST['html_zip_import_nonce_field'], 'html_zip_import_nonce')) {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode('Security check failed.')));
            exit;
        }
        
        if (!isset($_FILES['html_zip'])) {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode('No ZIP file uploaded.')));
            exit;
        }
        
        // Check file type
        $file_info = wp_check_filetype_and_ext($_FILES['html_zip']['tmp_name'], $_FILES['html_zip']['name']);
        if ($file_info['ext'] !== 'zip') {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode('Invalid file type. Please upload a ZIP file.')));
            exit;
        }
        
        // Check if Elementor option is selected but Elementor is not active
        $use_elementor = isset($_POST['use_elementor']) && $_POST['use_elementor'] == '1';
        if ($use_elementor && !HTML_WP_Elementor::is_elementor_active()) {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode('Elementor plugin is not active. Please install and activate Elementor first.')));
            exit;
        }
        
        $parent_page = isset($_POST['parent_page']) ? intval($_POST['parent_page']) : 0;
        $create_menu = isset($_POST['create_menu']) && $_POST['create_menu'] == '1';
        
        try {
            // Process the ZIP file
            $results = HTML_WP_Zip_Import::import_zip($_FILES['html_zip']['tmp_name'], $use_elementor, $parent_page, $create_menu);
            
            if (is_wp_error($results)) {
                wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode($results->get_error_message())));
                exit;
            }
            
            // Store results in transient for display
            set_transient('html_importer_results', $results, 300); // 5 minutes
            
            wp_redirect(admin_url('admin.php?page=html-importer&zip_import_results=1&import_success=' . count($results)));
            exit;
            
        } catch (Exception $e) {
            wp_redirect(admin_url('admin.php?page=html-importer&import_error=' . urlencode($e->getMessage())));
            exit;
        }
    }
    
    private function display_import_results($results) {
        ?>
        <div class="card">
            <h2>Import Results</h2>
            <p>Successfully imported <?php echo count($results); ?> pages:</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Page Title</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?php echo esc_html($result['file']); ?></td>
                            <td><?php echo esc_html($result['title']); ?></td>
                            <td>
                                <a href="<?php echo esc_url($result['edit_link']); ?>" class="button button-small">Edit Page</a>
                                <a href="<?php echo esc_url($result['view_link']); ?>" class="button button-small" target="_blank">View Page</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Initialize the admin UI
new HTML_WP_Admin_UI();