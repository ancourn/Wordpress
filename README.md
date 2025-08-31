# HTML to WordPress Importer

A powerful WordPress plugin that allows users to upload HTML files or ZIP archives and converts them into WordPress Pages with proper asset handling, automatic navigation menu generation, and Elementor integration.

## 🚀 Features

### Core Functionality
- **Single HTML File Import**: Upload individual HTML files and convert them to WordPress pages
- **ZIP Archive Import**: Upload ZIP archives containing multiple HTML files for bulk import
- **Asset Management**: Automatically copies CSS, JS, images, and fonts to the WordPress uploads directory
- **URL Rewriting**: Rewrites all asset links in HTML to point to new WordPress upload paths
- **Navigation Menu**: Automatically generates navigation menu linking imported pages
- **Title Extraction**: Automatically extracts page titles from `<title>` tags or generates defaults
- **Content Extraction**: Extracts content from `<body>` tags while removing unwanted elements

### Advanced Features
- **Elementor Integration**: Convert HTML content to editable Elementor blocks and widgets
- **Smart Asset Handler**: Comprehensive asset management with subdirectory support
- **Duplicate Prevention**: Intelligent handling of duplicate filenames from different directories
- **Modern Admin Interface**: Clean, tabbed interface for single file and ZIP imports
- **Detailed Results**: Comprehensive import results with edit and view links
- **Error Handling**: Robust error handling and user feedback

### Supported File Types
- **HTML Files**: `.html`, `.htm`
- **ZIP Archives**: `.zip` containing HTML files and assets
- **Asset Types**: CSS, JS, PNG, JPG, JPEG, GIF, SVG, WOFF, WOFF2, TTF, OTF, EOT

## 📋 Requirements

### System Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.0 or higher
- **MySQL**: 5.6 or higher
- **Memory**: 64MB PHP memory limit (128MB recommended)
- **Disk Space**: 10MB for plugin + additional space for imported assets

### PHP Extensions
- **ZipArchive**: For ZIP file processing
- **DOMDocument**: For HTML parsing
- **Fileinfo**: For file type detection
- **JSON**: For WordPress compatibility
- **MBString**: For string handling
- **cURL**: For WordPress updates and communication

### Optional Dependencies
- **Elementor Plugin**: For Elementor block conversion (optional)
- **GD Library**: For image processing (optional)
- **Imagick**: For advanced image processing (optional)

## 🛠️ Installation

### Method 1: WordPress Admin (Recommended)

1. **Download the Plugin**
   ```bash
   # Download from GitHub
   wget https://github.com/ancourn/Wordpress/archive/main.zip
   unzip main.zip
   ```

2. **Upload to WordPress**
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Select the plugin ZIP file
   - Click "Install Now"

3. **Activate the Plugin**
   - After installation, click "Activate Plugin"
   - The plugin will appear in the WordPress admin menu

### Method 2: Manual Installation

1. **Extract the Plugin**
   ```bash
   unzip html-to-wp-importer.zip
   ```

2. **Upload via FTP/SFTP**
   ```bash
   # Upload to WordPress plugins directory
   scp -r html-to-wp-importer/ user@server:/path/to/wp-content/plugins/
   ```

3. **Activate in WordPress**
   - Go to WordPress Admin → Plugins
   - Find "HTML to WordPress Importer"
   - Click "Activate"

### Method 3: Command Line (WP-CLI)
```bash
# Install plugin via WP-CLI
wp plugin install /path/to/html-to-wp-importer.zip --activate

# Or if already uploaded
wp plugin activate html-to-wp-importer
```

## 🎯 Usage

### Basic Import

1. **Access the Plugin**
   - Go to WordPress Admin → **HTML Importer**
   - You'll see the main import interface with two tabs

2. **Single File Import**
   - Click the "Single File" tab
   - Upload an HTML file
   - Configure options (parent page, menu creation, Elementor conversion)
   - Click "Upload & Import"

3. **ZIP Archive Import**
   - Click the "ZIP Archive" tab
   - Upload a ZIP file containing HTML files and assets
   - Configure options
   - Click "Upload & Import ZIP"

### Advanced Options

#### Parent Page Selection
- Choose a parent page if you want imported pages to be children
- Pages will be nested under the selected parent

#### Navigation Menu Creation
- Automatically creates a navigation menu with all imported pages
- Menu is assigned to the primary theme location when possible
- Can be disabled if not needed

#### Elementor Integration
- Convert HTML content to editable Elementor blocks
- Only available when Elementor plugin is active
- Creates fully editable Elementor pages instead of standard WordPress pages

#### Overwrite Existing Pages
- Replace existing pages with the same title
- Useful for updating previously imported content

## 📁 Project Structure

```
html-to-wp-importer/
├── html-to-wp-importer.php       # Main plugin bootstrap
├── includes/
│   ├── parser.php               # HTML parsing and page creation
│   ├── assets.php               # Legacy asset handling
│   ├── elementor.php            # Elementor integration
│   ├── elementor-mapper.php     # HTML to Elementor conversion
│   ├── zip-import.php           # ZIP archive processing
│   ├── asset-handler.php       # Advanced asset management
│   ├── menu.php                # Navigation menu management
│   └── admin-ui.php            # Modern admin interface
├── admin/
│   └── uploader.php            # Legacy admin upload form
├── assets/
│   ├── css/admin.css            # Admin styling
│   └── js/admin.js              # Admin JavaScript
├── docs/
│   ├── DEPENDENCIES.md          # System requirements and technical specs
│   └── SETUP.md                 # Installation and configuration guide
├── .gitignore                    # Git ignore rules
├── README.md                     # This file
├── PROJECT_MANIFEST.md          # Project status and development roadmap
├── GITHUB_SETUP_SUMMARY.md     # GitHub setup details
└── LICENSE                      # GPL v2 license
```

## 🔧 How It Works

### For ZIP Files
1. **Upload**: User uploads ZIP archive
2. **Extract**: Plugin extracts to unique temporary directory
3. **Process**: Finds and processes all HTML files (including subdirectories)
4. **Assets**: Copies all assets (CSS, JS, images, fonts) to uploads directory
5. **Rewrite**: Rewrites all asset URLs in HTML content to point to new locations
6. **Pages**: Creates WordPress pages for each HTML file
7. **Menu**: Creates navigation menu with all imported pages
8. **Cleanup**: Removes temporary files

### For Single HTML Files
1. **Upload**: User uploads single HTML file
2. **Process**: Processes the HTML content directly
3. **Assets**: Handles any assets referenced in the HTML
4. **Rewrite**: Rewrites asset URLs to point to uploads directory
5. **Page**: Creates single WordPress page
6. **Menu**: Creates navigation menu with the imported page

### Asset URL Rewriting
The plugin automatically rewrites the following asset URLs:
- **Images**: `<img src="image.jpg">` → `<img src="/wp-content/uploads/html_import_assets/image.jpg">`
- **CSS**: `<link href="style.css">` → `<link href="/wp-content/uploads/html_import_assets/style.css">`
- **JavaScript**: `<script src="script.js">` → `<script src="/wp-content/uploads/html_import_assets/script.js">`
- **Background Images**: `background-image: url("bg.jpg")` → `background-image: url("/wp-content/uploads/html_import_assets/bg.jpg")`

## 🛡️ Security Features

- **Nonce Verification**: Protects against CSRF attacks
- **File Type Validation**: Only allows HTML and ZIP files
- **File Size Limits**: Prevents excessively large uploads
- **Sanitization**: Proper sanitization of all input and output
- **Capability Checks**: Ensures only authorized users can import files
- **Path Validation**: Prevents directory traversal attacks
- **Content Filtering**: Removal of potentially harmful content

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

**"Elementor plugin is not active"**
- Install and activate the Elementor plugin
- The Elementor option will only appear when Elementor is active

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
- Elementor Free and Pro versions

## 📊 Performance

- **Memory Usage**: Optimized for minimal memory footprint
- **File Processing**: Efficient file handling with proper cleanup
- **Database Queries**: Minimal database operations
- **Asset Management**: Automatic cleanup of old assets (30-day retention)

## 🤝 Contributing

1. **Fork the Repository**
2. **Create Feature Branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Commit Your Changes**
   ```bash
   git commit -m 'Add some amazing feature'
   ```
4. **Push to Branch**
   ```bash
   git push origin feature/amazing-feature
   ```
5. **Open Pull Request**

### Development Environment Setup
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
- Elementor team for the powerful page builder
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
- Elementor integration
- Advanced asset handler
- Modern tabbed admin interface
- Comprehensive error handling

---

**Note**: A backup snapshot was created before pushing to GitHub: `backup-before-github-push.tar.gz`