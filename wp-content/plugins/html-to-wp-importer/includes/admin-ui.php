<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h1>HTML to WordPress Importer</h1>
    <p class="description">Upload HTML files or ZIP archives to convert them into WordPress pages with proper asset handling and Elementor integration.</p>
    
    <?php
    // Display success/error messages
    if (isset($_GET['queued'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Import job has been queued and will be processed in the background.</p></div>';
    }
    
    if (isset($_GET['result']) && $_GET['result'] === 'done') {
        echo '<div class="notice notice-success is-dismissible"><p>Import completed successfully!</p></div>';
    }
    
    if (isset($_GET['error'])) {
        $error = sanitize_text_field($_GET['error']);
        echo '<div class="notice notice-error is-dismissible"><p>Error: ' . esc_html($error) . '</p></div>';
    }
    ?>
    
    <div class="card">
        <h2>Upload ZIP Archive</h2>
        <p>Upload a ZIP file containing HTML files and assets to import them as WordPress pages.</p>
        
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="html_import_upload">
            <?php wp_nonce_field('html_import_upload_nonce', 'html_import_upload_nonce_field'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="html_zip">Select ZIP file:</label>
                    </th>
                    <td>
                        <input type="file" name="html_zip" id="html_zip" accept=".zip" required>
                        <p class="description">Upload a ZIP file containing HTML files and assets</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Upload & Import', 'primary', 'submit', true, ['id' => 'import-button']); ?>
        </form>
        
        <div id="import-progress" style="display: none; margin-top: 20px;">
            <div class="progress-bar">
                <div class="progress" style="width: 0%"></div>
            </div>
            <p class="progress-text">Processing...</p>
        </div>
    </div>
    
    <div class="card">
        <h3>How it works:</h3>
        <ol>
            <li>Upload a ZIP file containing HTML files and assets (CSS, JS, images)</li>
            <li>The plugin will extract the ZIP and process all HTML files</li>
            <li>Assets will be copied to the WordPress uploads directory</li>
            <li>HTML content will be converted to Elementor blocks</li>
            <li>WordPress pages will be created for each HTML file</li>
            <li>A navigation menu will be created linking all imported pages</li>
            <li>Internal links will be rewritten to point to the new pages</li>
        </ol>
    </div>
    
    <div class="card">
        <h3>ZIP Archive Requirements</h3>
        <ul>
            <li>ZIP file should contain HTML files (.html or .htm)</li>
            <li>Assets (CSS, JS, images) will be automatically processed</li>
            <li>HTML files can be in root directory or subdirectories</li>
            <li>Page titles will be extracted from HTML title tags or filenames</li>
            <li>Navigation menu will be created automatically</li>
            <li>Elementor integration requires the Elementor plugin to be active</li>
        </ul>
    </div>
    
    <div class="card">
        <h3>Features</h3>
        <ul>
            <li><strong>Asset Management:</strong> Automatically copies and manages all assets</li>
            <li><strong>URL Rewriting:</strong> Rewrites all asset URLs to point to new locations</li>
            <li><strong>Elementor Integration:</strong> Converts HTML to editable Elementor blocks</li>
            <li><strong>Menu Generation:</strong> Creates navigation menu from imported pages</li>
            <li><strong>Link Mapping:</strong> Rewrites internal links between imported pages</li>
            <li><strong>Background Processing:</strong> Uses Action Scheduler for large imports</li>
        </ul>
    </div>
    
    <?php if (class_exists('ActionScheduler')): ?>
    <div class="card">
        <h3>Background Jobs</h3>
        <p>Background processing is enabled. Large imports will be processed in the background.</p>
        <p><a href="<?php echo esc_url(admin_url('tools.php?page=action-scheduler')); ?>" class="button">View Scheduled Actions</a></p>
    </div>
    <?php endif; ?>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
    padding: 15px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.card h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress {
    height: 100%;
    background-color: #0073aa;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    font-weight: bold;
    color: #333;
}

#import-button {
    position: relative;
}

#import-button.loading {
    pointer-events: none;
    opacity: 0.7;
}

#import-button.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('form').on('submit', function(e) {
        var $button = $('#import-button');
        var $progress = $('#import-progress');
        var $progressBar = $progress.find('.progress');
        var $progressText = $progress.find('.progress-text');
        
        $button.addClass('loading');
        $button.prop('disabled', true);
        $progress.show();
        
        // Simulate progress (in a real implementation, this would be updated via AJAX)
        var progress = 0;
        var interval = setInterval(function() {
            progress += Math.random() * 20;
            if (progress > 90) progress = 90;
            
            $progressBar.css('width', progress + '%');
            $progressText.text('Processing... ' + Math.round(progress) + '%');
            
            if (progress >= 90) {
                clearInterval(interval);
            }
        }, 500);
    });
});
</script>