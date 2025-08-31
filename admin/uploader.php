<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display the file upload form
 */
function html_to_wp_importer_upload_form() {
    ?>
    <div class="html-importer-form-container">
        <form method="post" enctype="multipart/form-data" class="html-importer-form">
            <?php wp_nonce_field('html_to_wp_importer_upload', 'html_importer_nonce'); ?>
            
            <div class="form-field">
                <label for="html_file"><?php echo esc_html__('Select HTML file or ZIP archive:', 'html-to-wp-importer'); ?></label>
                <input type="file" name="html_file" id="html_file" accept=".html,.htm,.zip" required>
                <p class="description"><?php echo esc_html__('Supported formats: .html, .htm, .zip', 'html-to-wp-importer'); ?></p>
            </div>
            
            <div class="form-field">
                <label for="parent_page"><?php echo esc_html__('Parent Page (optional):', 'html-to-wp-importer'); ?></label>
                <?php
                wp_dropdown_pages(array(
                    'name' => 'parent_page',
                    'id' => 'parent_page',
                    'show_option_none' => __('— No Parent —', 'html-to-wp-importer'),
                    'option_none_value' => '0',
                ));
                ?>
            </div>
            
            <div class="form-field">
                <label>
                    <input type="checkbox" name="create_menu" id="create_menu" value="1" checked>
                    <?php echo esc_html__('Create navigation menu from imported pages', 'html-to-wp-importer'); ?>
                </label>
            </div>
            
            <div class="form-field">
                <label>
                    <input type="checkbox" name="overwrite_existing" id="overwrite_existing" value="1">
                    <?php echo esc_html__('Overwrite existing pages with same title', 'html-to-wp-importer'); ?>
                </label>
            </div>
            
            <?php submit_button(__('Import HTML Files', 'html-to-wp-importer'), 'primary', 'submit'); ?>
        </form>
    </div>
    
    <div class="html-importer-info">
        <h3><?php echo esc_html__('How it works:', 'html-to-wp-importer'); ?></h3>
        <ul>
            <li><?php echo esc_html__('Upload a single HTML file or a ZIP archive containing multiple HTML files', 'html-to-wp-importer'); ?></li>
            <li><?php echo esc_html__('The plugin will extract the title from &lt;title&gt; tag and content from &lt;body&gt; tag', 'html-to-wp-importer'); ?></li>
            <li><?php echo esc_html__('All assets (CSS, JS, images) will be copied to the uploads folder', 'html-to-wp-importer'); ?></li>
            <li><?php echo esc_html__('Links in HTML will be rewritten to point to the new asset locations', 'html-to-wp-importer'); ?></li>
            <li><?php echo esc_html__('Each HTML file will become a published WordPress page', 'html-to-wp-importer'); ?></li>
        </ul>
    </div>
    <?php
}

/**
 * Handle file upload and processing
 */
function html_to_wp_importer_handle_upload() {
    // Verify nonce
    if (!isset($_POST['html_importer_nonce']) || !wp_verify_nonce($_POST['html_importer_nonce'], 'html_to_wp_importer_upload')) {
        wp_redirect(admin_url('admin.php?page=html-to-wp-importer&import_error=' . urlencode('Security check failed.')));
        exit;
    }
    
    // Check file upload
    if (!isset($_FILES['html_file']) || $_FILES['html_file']['error'] !== UPLOAD_ERR_OK) {
        $error_message = isset($_FILES['html_file']['error']) ? html_to_wp_importer_get_upload_error_message($_FILES['html_file']['error']) : 'No file uploaded.';
        wp_redirect(admin_url('admin.php?page=html-to-wp-importer&import_error=' . urlencode($error_message)));
        exit;
    }
    
    $file_info = wp_check_filetype_and_ext($_FILES['html_file']['tmp_name'], $_FILES['html_file']['name']);
    $allowed_types = array('html', 'htm', 'zip');
    
    if (!in_array(strtolower($file_info['ext']), $allowed_types)) {
        wp_redirect(admin_url('admin.php?page=html-to-wp-importer&import_error=' . urlencode('Invalid file type. Only HTML and ZIP files are allowed.')));
        exit;
    }
    
    $parent_page = isset($_POST['parent_page']) ? intval($_POST['parent_page']) : 0;
    $create_menu = isset($_POST['create_menu']) ? true : false;
    $overwrite_existing = isset($_POST['overwrite_existing']) ? true : false;
    
    // Process the file
    try {
        if ($file_info['ext'] === 'zip') {
            $imported_pages = html_to_wp_importer_process_zip($_FILES['html_file']['tmp_name'], $parent_page, $overwrite_existing);
        } else {
            $imported_pages = html_to_wp_importer_process_html($_FILES['html_file']['tmp_name'], $_FILES['html_file']['name'], $parent_page, $overwrite_existing);
        }
        
        // Create navigation menu if requested
        if ($create_menu && !empty($imported_pages)) {
            html_to_wp_importer_create_navigation_menu($imported_pages);
        }
        
        wp_redirect(admin_url('admin.php?page=html-to-wp-importer&import_success=' . count($imported_pages)));
        exit;
        
    } catch (Exception $e) {
        wp_redirect(admin_url('admin.php?page=html-to-wp-importer&import_error=' . urlencode($e->getMessage())));
        exit;
    }
}

/**
 * Get upload error message
 */
function html_to_wp_importer_get_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk.';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload.';
        default:
            return 'Unknown upload error.';
    }
}

/**
 * Process ZIP file
 */
function html_to_wp_importer_process_zip($zip_file, $parent_page, $overwrite_existing) {
    if (!class_exists('ZipArchive')) {
        throw new Exception('ZipArchive class not available. Please enable PHP zip extension.');
    }
    
    $zip = new ZipArchive();
    if ($zip->open($zip_file) !== true) {
        throw new Exception('Failed to open ZIP file.');
    }
    
    $imported_pages = array();
    $temp_dir = sys_get_temp_dir() . '/html_importer_' . uniqid();
    
    if (!wp_mkdir_p($temp_dir)) {
        throw new Exception('Failed to create temporary directory.');
    }
    
    // Extract ZIP file
    $zip->extractTo($temp_dir);
    $zip->close();
    
    // Find all HTML files
    $html_files = glob($temp_dir . '/*.{html,htm}', GLOB_BRACE);
    
    if (empty($html_files)) {
        // Look for HTML files in subdirectories
        $html_files = glob($temp_dir . '/**/*.{html,htm}', GLOB_BRACE);
    }
    
    if (empty($html_files)) {
        throw new Exception('No HTML files found in the ZIP archive.');
    }
    
    // Process each HTML file
    foreach ($html_files as $html_file) {
        $filename = basename($html_file);
        try {
            $page_id = html_to_wp_importer_process_html($html_file, $filename, $parent_page, $overwrite_existing);
            if ($page_id) {
                $imported_pages[] = $page_id;
            }
        } catch (Exception $e) {
            // Continue processing other files even if one fails
            error_log('HTML Importer: Failed to process ' . $filename . ': ' . $e->getMessage());
        }
    }
    
    // Clean up temporary directory
    html_to_wp_importer_recursive_rmdir($temp_dir);
    
    return $imported_pages;
}

/**
 * Process single HTML file
 */
function html_to_wp_importer_process_html($html_file, $filename, $parent_page, $overwrite_existing) {
    if (!file_exists($html_file)) {
        throw new Exception('HTML file not found: ' . $html_file);
    }
    
    $html_content = file_get_contents($html_file);
    if ($html_content === false) {
        throw new Exception('Failed to read HTML file: ' . $filename);
    }
    
    // Parse HTML content
    $parsed_data = html_to_wp_importer_parse_html($html_content);
    
    // Check if page with same title already exists
    $existing_page = get_page_by_title($parsed_data['title'], OBJECT, 'page');
    
    if ($existing_page && !$overwrite_existing) {
        throw new Exception('Page with title "' . $parsed_data['title'] . '" already exists. Enable overwrite option to replace it.');
    }
    
    // Handle assets
    $asset_dir = dirname($html_file);
    $rewritten_content = html_to_wp_importer_handle_assets($parsed_data['content'], $asset_dir, $filename);
    
    // Create or update page
    $page_data = array(
        'post_title'   => $parsed_data['title'],
        'post_content' => $rewritten_content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_parent'  => $parent_page,
    );
    
    if ($existing_page && $overwrite_existing) {
        $page_data['ID'] = $existing_page->ID;
        $page_id = wp_update_post($page_data);
    } else {
        $page_id = wp_insert_post($page_data);
    }
    
    if (is_wp_error($page_id)) {
        throw new Exception('Failed to create/update page: ' . $page_id->get_error_message());
    }
    
    return $page_id;
}

/**
 * Recursive directory removal
 */
function html_to_wp_importer_recursive_rmdir($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        if (!html_to_wp_importer_recursive_rmdir($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}