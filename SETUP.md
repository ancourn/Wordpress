# HTML to WordPress Importer - Setup Guide

## 🚀 Quick Start Guide

This guide will help you set up the HTML to WordPress Importer plugin for development and deployment.

### 📋 Prerequisites

Before you begin, ensure you have:

- **WordPress 5.0+** installed and running
- **PHP 7.0+** with required extensions
- **MySQL 5.6+** database
- **Git** for version control
- **FTP/SFTP access** (for manual installation)
- **Administrator access** to WordPress admin

### 🛠️ Installation Methods

#### Method 1: Clone from GitHub (Recommended for Development)

```bash
# 1. Clone the repository
git clone -b feature-complete-v1.0 https://github.com/ancourn/Wordpress.git
cd Wordpress/html-to-wp-importer

# 2. Review the documentation
cat README.md
cat PROJECT_MANIFEST.md
cat SETUP.md

# 3. Set up WordPress environment
# Use your preferred local development setup:
# - Local by Flywheel
# - XAMPP
# - MAMP
# - Docker with WordPress image
# - Vagrant with VVV

# 4. Install the plugin
# Option A: Manual installation
cp -r html-to-wp-importer /path/to/wordpress/wp-content/plugins/

# Option B: Create ZIP for WordPress admin upload
zip -r html-to-wp-importer.zip html-to-wp-importer/
```

#### Method 2: Direct WordPress Admin Upload

```bash
# 1. Create plugin ZIP file
cd wordpress-project
zip -r html-to-wp-importer.zip html-to-wp-importer/

# 2. Upload to WordPress
# - Go to WordPress Admin → Plugins → Add New
# - Click "Upload Plugin"
# - Select html-to-wp-importer.zip
# - Click "Install Now"
# - Activate the plugin
```

#### Method 3: WP-CLI Installation

```bash
# 1. Clone the repository
git clone -b feature-complete-v1.0 https://github.com/ancourn/Wordpress.git
cd Wordpress/html-to-wp-importer

# 2. Install via WP-CLI
wp plugin install ./html-to-wp-importer.zip --activate

# Or if already uploaded:
wp plugin activate html-to-wp-importer
```

### 🔧 Configuration

#### PHP Configuration

Edit your `php.ini` file:
```ini
; File upload settings
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 128M
max_execution_time = 300
max_input_time = 300

; Required extensions
extension=zip
extension=dom
extension=fileinfo
extension=mbstring
```

#### WordPress Configuration

Add to `wp-config.php`:
```php
// Increase memory limit for imports
define('WP_MEMORY_LIMIT', '128M');

// Enable file uploads
define('ALLOW_UNFILTERED_UPLOADS', false);

// Security settings
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', true);

// Debug settings (development only)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### Server Configuration

**Apache (.htaccess)**
```apache
# Increase upload limits
<IfModule mod_php.c>
    php_value upload_max_filesize 50M
    php_value post_max_size 50M
    php_value memory_limit 128M
    php_value max_execution_time 300
</IfModule>

# WordPress standard rewrite rules
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```

**Nginx (nginx.conf)**
```nginx
# Server block configuration
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/wordpress;
    index index.php;

    # Increase upload limits
    client_max_body_size 50M;
    fastcgi_read_timeout 300s;

    # WordPress location blocks
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PHP_VALUE "upload_max_filesize=50M \n post_max_size=50M \n memory_limit=128M";
    }
}
```

### 🧪 Testing the Installation

#### Basic Functionality Test

1. **Create Test HTML File**
   ```html
   <!DOCTYPE html>
   <html>
   <head>
       <title>Test Page</title>
       <link rel="stylesheet" href="style.css">
   </head>
   <body>
       <h1>Test Content</h1>
       <p>This is a test paragraph.</p>
       <img src="test.jpg" alt="Test Image">
       <script src="script.js"></script>
   </body>
   </html>
   ```

2. **Create Test Assets**
   ```bash
   # Create test assets directory
   mkdir test-import
   cd test-import
   
   # Create test files
   echo "body { background: #f0f0f0; }" > style.css
   echo "console.log('test');" > script.js
   # Create a small test image (1x1 pixel)
   echo "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==" | base64 -d > test.jpg
   ```

3. **Test Single HTML Import**
   - Go to WordPress Admin → HTML Importer
   - Click "Single File" tab
   - Upload your test HTML file
   - Verify the page is created and assets are properly linked

4. **Test ZIP Import**
   ```bash
   # Create ZIP file
   zip -r test-import.zip test-import/
   ```
   - Go to WordPress Admin → HTML Importer
   - Click "ZIP Archive" tab
   - Upload the ZIP file
   - Verify all pages are created and assets work

#### Advanced Testing

1. **Elementor Integration Test**
   - Install and activate Elementor plugin
   - Upload HTML file with Elementor option selected
   - Verify page is created with Elementor blocks
   - Edit page in Elementor to confirm blocks are editable

2. **Complex Structure Test**
   - Create ZIP with nested directories
   - Include duplicate filenames in different folders
   - Test asset URL rewriting
   - Verify all assets are properly handled

3. **Error Handling Test**
   - Upload invalid file types
   - Upload corrupted ZIP files
   - Test with insufficient permissions
   - Verify error messages are helpful

### 🔍 Troubleshooting

#### Common Issues

**Plugin doesn't appear in WordPress admin**
```bash
# Check plugin directory permissions
ls -la /var/www/wordpress/wp-content/plugins/

# Ensure directory is readable
chmod 755 /var/www/wordpress/wp-content/plugins/html-to-wp-importer/
```

**White screen after activation**
```php
// Enable debug mode in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);

// Check error logs
tail -f /var/log/php_errors.log
tail -f /var/log/wordpress/debug.log
```

**File upload fails**
```bash
# Check PHP upload settings
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Check directory permissions
ls -la /var/www/wordpress/wp-content/uploads/
```

**ZIP file extraction fails**
```bash
# Check if ZIP extension is enabled
php -m | grep zip

# Test ZIP functionality
php -r "
$zip = new ZipArchive();
if ($zip->open('test.zip') === TRUE) {
    echo 'ZIP extension working correctly';
    $zip->close();
} else {
    echo 'ZIP extension not working';
}
"
```

**Elementor integration not working**
```bash
# Check if Elementor is active
wp plugin status elementor

# Verify Elementor files exist
ls -la /var/www/wordpress/wp-content/plugins/elementor/
```

### 📊 Development Workflow

#### Setting Up Development Environment

```bash
# 1. Clone the repository
git clone -b feature-complete-v1.0 https://github.com/ancourn/Wordpress.git
cd Wordpress/html-to-wp-importer

# 2. Create development branch
git checkout -b dev/your-feature-name

# 3. Set up WordPress environment
# Use your preferred local development setup

# 4. Install plugin in development mode
ln -s /path/to/html-to-wp-importer /var/www/wordpress/wp-content/plugins/

# 5. Enable WordPress debug mode
# Edit wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

#### Making Changes

```bash
# 1. Make your changes
# Edit files as needed

# 2. Test your changes
# Test in WordPress admin and verify functionality

# 3. Commit changes
git add .
git commit -m "Describe your changes"

# 4. Push to repository
git push origin dev/your-feature-name

# 5. Create pull request
# Visit GitHub to create PR against feature-complete-v1.0
```

#### Code Quality

```bash
# Check PHP syntax
find . -name "*.php" -exec php -l {} \;

# Check WordPress coding standards
# Install WordPress Coding Standards first
composer require wp-coding-standards/wpcs
./vendor/bin/phpcs --standard=WordPress includes/
```

### 🔄 Deployment

#### Creating Release

```bash
# 1. Update version numbers
# Edit html-to-wp-importer.php
# Update PROJECT_MANIFEST.md

# 2. Create release ZIP
zip -r html-to-wp-importer-v1.0.zip html-to-wp-importer/

# 3. Test release ZIP
# Install in fresh WordPress installation

# 4. Tag release
git tag -a v1.0 -m "Version 1.0 Release"
git push origin v1.0
```

#### Updating Production

```bash
# 1. Backup current installation
cp -r /var/www/wordpress/wp-content/plugins/html-to-wp-importer /backup/

# 2. Deploy new version
# Upload new ZIP via WordPress admin or use WP-CLI
wp plugin update html-to-wp-importer

# 3. Verify functionality
# Test import functionality
# Check for errors
```

### 📚 Additional Resources

#### Documentation
- **README.md**: Project overview and usage instructions
- **PROJECT_MANIFEST.md**: Development roadmap and status
- **docs/DEPENDENCIES.md**: System requirements and technical specs
- **docs/SETUP.md**: Installation and configuration guide

#### Support
- **GitHub Issues**: Report bugs and request features
- **WordPress.org Forums**: Community support
- **Email Support**: For critical issues

#### Development Tools
- **Local by Flywheel**: Local WordPress development
- **WP-CLI**: Command-line WordPress management
- **Query Monitor**: Debug and optimize WordPress
- **Debug Bar**: WordPress debugging toolbar

---

## 🎯 Summary

The HTML to WordPress Importer plugin is now fully set up and ready for development and deployment. The plugin provides comprehensive functionality for importing HTML files and ZIP archives into WordPress with advanced asset management, Elementor integration, and a modern admin interface.

**Key Features Implemented:**
- Single HTML file and ZIP archive import
- Advanced asset management with subdirectory support
- Elementor block conversion
- Modern tabbed admin interface
- Comprehensive error handling
- Detailed documentation

**Backup Information:**
- **Backup Created**: `backup-before-github-push.tar.gz`
- **Repository**: `https://github.com/ancourn/Wordpress.git`
- **Branch**: `feature-complete-v1.0`

The plugin is now ready for production use and continued development.