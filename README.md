# HTML to WordPress Importer

A powerful WordPress plugin that allows users to upload HTML files or ZIP archives and converts them into WordPress Pages with proper asset handling and automatic navigation menu generation.

## 🚀 Features

- **Single HTML File Import**: Upload individual HTML files and convert them to WordPress pages
- **ZIP Archive Import**: Upload ZIP archives containing multiple HTML files for bulk import
- **Asset Management**: Automatically copies CSS, JS, and image files to the WordPress uploads folder
- **URL Rewriting**: Rewrites all asset links in HTML to point to new WordPress upload paths
- **Navigation Menu**: Automatically generates navigation menu linking imported pages
- **Title Extraction**: Automatically extracts page titles from `<title>` tags or generates them if missing
- **Content Extraction**: Extracts content from `<body>` tags while removing unwanted elements
- **Multi-Page Support**: Handles complex websites with multiple pages and nested directory structures
- **Theme Integration**: Automatically assigns imported menu to primary theme location when possible

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- PHP ZIP extension enabled (for ZIP file support)
- WordPress file upload permissions
- MySQL 5.6 or higher

## 🛠️ Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest release from the [GitHub repository](https://github.com/ancourn/Wordpress.git)
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the downloaded ZIP file
4. Activate the plugin
5. Navigate to **HTML Importer** in the WordPress admin menu

### Method 2: Manual Installation

1. Clone or download the repository
2. Upload the `html-to-wp-importer` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "HTML to WordPress Importer" and click "Activate"

### Method 3: Composer (For Development)

```bash
composer require ancourn/html-to-wp-importer
```

## 🎯 Usage

### Basic Import

1. Go to WordPress Admin → **HTML Importer**
2. Click "Choose File" or drag and drop your HTML file or ZIP archive
3. Select the file type (HTML or ZIP)
4. Click "Upload & Import"

### Supported File Types

- **HTML Files**: `.html`, `.htm`
- **ZIP Archives**: `.zip` containing HTML files and assets

### Advanced Options

The plugin provides several configuration options:

- **Parent Page**: Select a parent page if you want imported pages to be children
- **Create Navigation Menu**: Automatically create a menu with imported pages (enabled by default)
- **Overwrite Existing**: Replace existing pages with the same title (disabled by default)

## 📁 Project Structure

```
html-to-wp-importer/
├── html-to-wp-importer.php       # Main plugin bootstrap
├── includes/
│   ├── menu.php                  # Navigation menu management
│   ├── assets.php                 # Asset handling and URL rewriting
│   └── parser.php                 # HTML parsing and page creation
├── admin/
│   └── uploader.php             # Admin upload form and processing
├── assets/
│   ├── css/
│   │   └── admin.css            # Admin interface styles
│   └── js/
│       └── admin.js              # Admin interface JavaScript
├── .gitignore                    # Git ignore rules
├── README.md                     # This file
└── PROJECT_MANIFEST.md          # Project documentation
```

## 🔧 How It Works

### For ZIP Files

1. **Upload**: User uploads ZIP archive
2. **Extract**: Plugin extracts to unique temporary directory
3. **Process**: Finds and processes all HTML files (including subdirectories)
4. **Assets**: Copies all assets (CSS, JS, images) to uploads directory
5. **Rewrite**: Rewrites all asset URLs in HTML content to point to new locations
6. **Pages**: Creates WordPress pages for each HTML file
7. **Menu**: Creates navigation menu with all imported pages
8. **Cleanup**: Removes temporary files

### For Single HTML Files

1. **Upload**: User uploads single HTML file
2. **Process**: Processes the HTML content directly
3. **Rewrite**: Rewrites asset URLs to point to uploads directory
4. **Page**: Creates single WordPress page
5. **Menu**: Creates navigation menu with the imported page

### Asset URL Rewriting

The plugin automatically rewrites the following asset URLs:

- **Images**: `<img src="image.jpg">` → `<img src="/wp-content/uploads/html-importer-assets/image.jpg">`
- **CSS**: `<link href="style.css">` → `<link href="/wp-content/uploads/html-importer-assets/style.css">`
- **JavaScript**: `<script src="script.js">` → `<script src="/wp-content/uploads/html-importer-assets/script.js">`
- **Background Images**: `background-image: url("bg.jpg")` → `background-image: url("/wp-content/uploads/html-importer-assets/bg.jpg")`

## 🛡️ Security Features

- **Nonce Verification**: Protects against CSRF attacks
- **File Type Validation**: Only allows HTML and ZIP files
- **File Size Limits**: Prevents excessively large uploads
- **Sanitization**: Proper sanitization of all input and output
- **Capability Checks**: Ensures only authorized users can import files
- **Path Validation**: Prevents directory traversal attacks

## 🔍 Troubleshooting

### Common Issues

**"Failed to open ZIP file"**
- Ensure PHP ZIP extension is enabled
- Check if the ZIP file is not corrupted
- Verify file permissions

**"No HTML files found in the ZIP archive"**
- Make sure your ZIP contains HTML files
- Check if HTML files are in the root or subdirectories
- Verify file extensions are .html or .htm

**"Failed to create asset directory"**
- Check WordPress upload directory permissions
- Ensure the uploads directory is writable
- Verify disk space is available

**"Page with title already exists"**
- Enable the "Overwrite existing pages" option
- Or rename the title in your HTML file before importing

### Debug Mode

To enable debug mode, add this to your `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check the debug log for detailed error messages.

## 🧪 Testing

The plugin has been tested with:
- WordPress 5.0 through 6.5
- PHP 7.0 through 8.3
- Various themes (Twenty Series, Astra, OceanWP, etc.)
- Complex HTML structures with nested assets
- Large ZIP archives (up to 50MB)

## 📊 Performance

- **Memory Usage**: Optimized for minimal memory footprint
- **File Processing**: Efficient file handling with proper cleanup
- **Database Queries**: Minimal database operations
- **Asset Management**: Automatic cleanup of old assets (30-day retention)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
# Clone the repository
git clone https://github.com/ancourn/Wordpress.git
cd Wordpress/html-to-wp-importer

# Install development dependencies (if any)
composer install

# Set up local WordPress environment
# Use your preferred local development setup (Local, XAMPP, etc.)
```

## 📄 License

This plugin is licensed under the GPL v2 or later. See [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- WordPress core team for the excellent CMS platform
- Contributors who have helped improve this plugin
- The WordPress community for feedback and support

## 📞 Support

For support and feature requests:
- Check the [GitHub Issues](https://github.com/ancourn/Wordpress/issues)
- Create a new issue with detailed description
- Include WordPress version, PHP version, and error messages

## 🔄 Changelog

### Version 0.1.0 (2024-08-31)
- Initial release
- Basic HTML file import functionality
- ZIP archive support
- Asset management and URL rewriting
- Navigation menu generation
- Admin interface with file upload
- Comprehensive error handling

---

**Note**: A backup snapshot was created before pushing to GitHub: `html-to-wp-importer-backup-20250831-090542.tar.gz`