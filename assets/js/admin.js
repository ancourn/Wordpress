(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize file upload functionality
        initFileUpload();
        
        // Initialize form validation
        initFormValidation();
        
        // Initialize drag and drop
        initDragAndDrop();
        
        // Initialize progress tracking
        initProgressTracking();
    });

    /**
     * Initialize file upload functionality
     */
    function initFileUpload() {
        const fileInput = $('#html_file');
        const submitButton = $('#submit');
        
        // Show file name when selected
        fileInput.on('change', function() {
            const fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $('.html-importer-upload-text').text(fileName);
                submitButton.prop('disabled', false);
            } else {
                $('.html-importer-upload-text').text('Choose HTML file or ZIP archive');
                submitButton.prop('disabled', true);
            }
        });
        
        // Validate file type on selection
        fileInput.on('change', function() {
            const file = this.files[0];
            if (file) {
                const extension = file.name.split('.').pop().toLowerCase();
                const allowedExtensions = ['html', 'htm', 'zip'];
                
                if (!allowedExtensions.includes(extension)) {
                    showErrorMessage('Invalid file type. Please select an HTML file or ZIP archive.');
                    $(this).val('');
                    submitButton.prop('disabled', true);
                } else {
                    clearMessages();
                }
            }
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const form = $('.html-importer-form');
        
        form.on('submit', function(e) {
            e.preventDefault();
            
            const fileInput = $('#html_file')[0];
            const nonce = $('#html_importer_nonce').val();
            
            // Validate file selection
            if (!fileInput.files || fileInput.files.length === 0) {
                showErrorMessage('Please select a file to upload.');
                return;
            }
            
            // Validate nonce
            if (!nonce) {
                showErrorMessage('Security validation failed. Please refresh the page and try again.');
                return;
            }
            
            // Validate file size (max 50MB)
            const maxSize = 50 * 1024 * 1024; // 50MB
            if (fileInput.files[0].size > maxSize) {
                showErrorMessage('File size exceeds 50MB limit. Please choose a smaller file.');
                return;
            }
            
            // Show progress and submit form
            showProgress();
            this.submit();
        });
    }

    /**
     * Initialize drag and drop functionality
     */
    function initDragAndDrop() {
        const uploadArea = $('.html-importer-upload-area');
        const fileInput = $('#html_file');
        
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.on(eventName, preventDefaults, false);
            document.body.on(eventName, preventDefaults, false);
        });
        
        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.on(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.on(eventName, unhighlight, false);
        });
        
        // Handle dropped files
        uploadArea.on('drop', handleDrop, false);
        
        // Handle click to upload
        uploadArea.on('click', function() {
            fileInput.click();
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        function highlight(e) {
            uploadArea.addClass('dragover');
        }
        
        function unhighlight(e) {
            uploadArea.removeClass('dragover');
        }
        
        function handleDrop(e) {
            const dt = e.originalEvent.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput[0].files = files;
                fileInput.trigger('change');
            }
        }
    }

    /**
     * Initialize progress tracking
     */
    function initProgressTracking() {
        // Check if we have progress indicators in the URL
        const urlParams = new URLSearchParams(window.location.search);
        const successCount = urlParams.get('import_success');
        const errorMessage = urlParams.get('import_error');
        
        if (successCount) {
            showSuccessMessage(successCount + ' pages imported successfully!');
        } else if (errorMessage) {
            showErrorMessage(decodeURIComponent(errorMessage));
        }
    }

    /**
     * Show progress indicator
     */
    function showProgress() {
        const progressHtml = `
            <div class="html-importer-progress">
                <h3>Importing HTML files...</h3>
                <div class="html-importer-progress-bar">
                    <div class="html-importer-progress-bar-fill"></div>
                </div>
                <div class="html-importer-progress-text">Processing your files...</div>
            </div>
        `;
        
        $('.html-importer-form-container').after(progressHtml);
        
        // Simulate progress animation
        let progress = 0;
        const progressBar = $('.html-importer-progress-bar-fill');
        const progressText = $('.html-importer-progress-text');
        
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            
            progressBar.css('width', progress + '%');
            
            if (progress < 30) {
                progressText.text('Reading HTML content...');
            } else if (progress < 60) {
                progressText.text('Extracting assets...');
            } else if (progress < 90) {
                progressText.text('Creating WordPress pages...');
            }
        }, 500);
        
        // Store interval ID for cleanup
        $('.html-importer-progress').data('interval', interval);
    }

    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        const messageHtml = `
            <div class="html-importer-message success">
                <strong>Success!</strong> ${message}
            </div>
        `;
        
        $('.html-importer-form-container').before(messageHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            $('.html-importer-message.success').fadeOut();
        }, 5000);
    }

    /**
     * Show error message
     */
    function showErrorMessage(message) {
        const messageHtml = `
            <div class="html-importer-message error">
                <strong>Error!</strong> ${message}
            </div>
        `;
        
        $('.html-importer-form-container').before(messageHtml);
        
        // Auto-hide after 8 seconds
        setTimeout(() => {
            $('.html-importer-message.error').fadeOut();
        }, 8000);
    }

    /**
     * Show info message
     */
    function showInfoMessage(message) {
        const messageHtml = `
            <div class="html-importer-message info">
                <strong>Info:</strong> ${message}
            </div>
        `;
        
        $('.html-importer-form-container').before(messageHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            $('.html-importer-message.info').fadeOut();
        }, 5000);
    }

    /**
     * Clear all messages
     */
    function clearMessages() {
        $('.html-importer-message').remove();
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Check browser compatibility
     */
    function checkBrowserCompatibility() {
        const features = {
            'FileReader': typeof FileReader !== 'undefined',
            'FormData': typeof FormData !== 'undefined',
            'XMLHttpRequest': typeof XMLHttpRequest !== 'undefined',
            'Drag and Drop': 'draggable' in document.createElement('div')
        };
        
        let allSupported = true;
        for (const feature in features) {
            if (!features[feature]) {
                allSupported = false;
                showErrorMessage(`Your browser doesn't support ${feature}. Some features may not work properly.`);
                break;
            }
        }
        
        return allSupported;
    }

    // Check browser compatibility on load
    $(document).ready(function() {
        checkBrowserCompatibility();
    });

})(jQuery);