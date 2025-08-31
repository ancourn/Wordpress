# Setup and Installation Guide

## Quick Start Guide

This guide will help you set up the HTML to WordPress Importer plugin for development and deployment.

### 📋 Prerequisites

Before you begin, ensure you have:

#### For Docker Setup (Recommended)
- **Docker 20.0+** installed and running
- **Docker Compose 1.29+** installed
- **Git** for version control
- **Basic command line knowledge**

#### For Manual WordPress Setup
- **WordPress 5.0+** installed and running
- **PHP 7.0+** with required extensions
- **MySQL 5.6+** database
- **FTP/SFTP access** (for manual installation)
- **Administrator access** to WordPress admin

## 🚀 Installation Methods

### Method 1: Docker Setup (Recommended)

#### Step 1: Clone the Repository
```bash
git clone https://github.com/YOUR_USERNAME/html-to-wp-importer.git
cd html-to-wp-importer
```

#### Step 2: Start Docker Environment
```bash
docker-compose up -d
```

#### Step 3: Complete WordPress Installation
1. **Open your browser** and go to http://localhost:8080
2. **Select Language**: Choose your preferred language
3. **Database Configuration**: Use the pre-configured settings:
   - Database Name: `wordpress`
   - Username: `wp`
   - Password: `wp`
   - Database Host: `db`
   - Table Prefix: `wp_`
4. **Site Information**: Fill in your site details:
   - Site Title: Your site name
   - Username: Your admin username
   - Password: Your admin password
   - Email: Your email address
5. **Install WordPress**: Click "Install WordPress"
6. **Log In**: Use your admin credentials

#### Step 4: Activate the Plugin
1. **Go to WordPress Admin**: http://localhost:8080/wp-admin
2. **Navigate to Plugins**: Click "Plugins" in the left sidebar
3. **Find the Plugin**: Look for "HTML to WordPress Importer"
4. **Activate**: Click "Activate" below the plugin name

#### Step 5: Test the Plugin
1. **Go to HTML Importer**: Click "HTML Importer" in the left sidebar
2. **Upload Sample Site**: Use the `sample-site.tar.gz` file included in the repository
3. **Verify Results**: Check that pages are created and navigation menu is generated

### Method 2: Manual WordPress Installation

#### Step 1: Download the Plugin
```bash
# Option A: Download as ZIP
wget https://github.com/YOUR_USERNAME/html-to-wp-importer/archive/main.zip
unzip main.zip

# Option B: Clone Repository
git clone https://github.com/YOUR_USERNAME/html-to-wp-importer.git
cd html-to-wp-importer
```

#### Step 2: Upload to WordPress
1. **Create Plugin ZIP**:
   ```bash
   cd html-to-wp-importer
   zip -r html-to-wp-importer.zip wp-content/plugins/html-to-wp-importer/
   ```

2. **Upload via WordPress Admin**:
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Select `html-to-wp-importer.zip`
   - Click "Install Now"
   - Click "Activate Plugin"

#### Step 3: Alternative: Manual Upload via FTP
```bash
# Extract the plugin
unzip html-to-wp-importer.zip

# Upload via FTP/SFTP
scp -r html-to-wp-importer/wp-content/plugins/html-to-wp-importer/ \
  user@server:/path/to/wp-content/plugins/

# Or use your preferred FTP client
```

#### Step 4: Activate in WordPress
- Go to WordPress Admin → Plugins
- Find "HTML to WordPress Importer"
- Click "Activate"

### Method 3: WP-CLI Installation

#### Step 1: Clone Repository
```bash
git clone https://github.com/YOUR_USERNAME/html-to-wp-importer.git
cd html-to-wp-importer
```

#### Step 2: Install via WP-CLI
```bash
# If WordPress is installed locally
wp plugin install ./wp-content/plugins/html-to-wp-importer --activate

# Or if you have the plugin as a ZIP file
wp plugin install html-to-wp-importer.zip --activate
```

## ⚙️ Configuration

### PHP Configuration

#### Required PHP Extensions
Ensure these extensions are enabled in your `php.ini`:
```ini
extension=zip
extension=dom
extension=fileinfo
extension=mbstring
extension=gd
```

#### Recommended PHP Settings
Add these settings to your `php.ini`:
```ini
; File upload settings
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 128M
max_execution_time = 300
max_input_time = 300
```

### WordPress Configuration

#### Required WordPress Settings
Add these to your `wp-config.php`:
```php
// Increase memory limit for imports
define('WP_MEMORY_LIMIT', '128M');

// Enable file uploads
define('ALLOW_UNFILTERED_UPLOADS', false);

// Security settings
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', true);
```

#### Debug Settings (Development Only)
```php
// Enable debug mode for development
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Server Configuration

#### Apache Configuration
Add these settings to your `.htaccess` file:
```apache
# Increase upload limits
<IfModule mod_php.c>
    php_value upload_max_filesize 50M
    php_value post_max_size 50M
    php_value memory_limit 128M
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

#### Nginx Configuration
Add these settings to your nginx configuration:
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

## 🧪 Testing the Installation

### Basic Functionality Test

#### Step 1: Create Test HTML File
Create a simple HTML file:
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

#### Step 2: Create Test Assets
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

#### Step 3: Create ZIP Archive
```bash
# Create ZIP file
zip -r test-import.zip test-import/
```

#### Step 4: Test Single HTML Import
1. **Go to HTML Importer**: WordPress Admin → HTML Importer
2. **Upload HTML File**: Click "Choose File" and select your test HTML file
3. **Configure Options**:
   - Parent Page: Leave as default
   - Create Menu: Check this option
   - Use Elementor: Check if Elementor is installed
4. **Upload & Import**: Click the button
5. **Verify Results**:
   - Check that a page was created
   - Verify the navigation menu exists
   - Test that assets are properly linked

#### Step 5: Test ZIP Import
1. **Upload ZIP File**: Use the ZIP file you created
2. **Configure Options**: Same as above
3. **Upload & Import ZIP**: Click the button
4. **Verify Results**:
   - Check that all pages were created
   - Verify navigation menu includes all pages
   - Test all internal links work

### Advanced Testing

#### Elementor Integration Test
1. **Install Elementor**: Plugins → Add New → Search for "Elementor"
2. **Activate Elementor**: Click "Activate"
3. **Import with Elementor**: Upload HTML file with Elementor option checked
4. **Edit with Elementor**: Go to Pages → Edit with Elementor
5. **Verify Blocks**: Check that HTML was converted to Elementor blocks

#### Error Handling Test
1. **Invalid File Type**: Try uploading a .txt file
2. **Corrupted ZIP**: Upload a corrupted ZIP file
3. **Large File**: Try uploading a very large file
4. **Verify Error Messages**: Check that error messages are helpful

#### Performance Test
1. **Large ZIP Import**: Create a ZIP with many files
2. **Monitor Performance**: Check memory usage and processing time
3. **Background Processing**: Test with Action Scheduler if installed

## 🔍 Troubleshooting

### Common Issues

#### Plugin doesn't appear in WordPress admin
```bash
# Check plugin directory permissions
ls -la /var/www/wordpress/wp-content/plugins/

# Ensure directory is readable
chmod 755 /var/www/wordpress/wp-content/plugins/html-to-wp-importer/
```

#### White screen after activation
```php
// Enable debug mode in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);

// Check error logs
tail -f /var/log/php_errors.log
tail -f /var/log/wordpress/debug.log
```

#### File upload fails
```bash
# Check PHP upload settings
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Check directory permissions
ls -la /var/www/wordpress/wp-content/uploads/
```

#### ZIP file extraction fails
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

#### Elementor integration not working
```bash
# Check if Elementor is active
wp plugin status elementor

# Verify Elementor files exist
ls -la /var/www/wordpress/wp-content/plugins/elementor/
```

### Docker-Specific Issues

#### Container won't start
```bash
# Check Docker logs
docker-compose logs

# Check container status
docker-compose ps

# Restart containers
docker-compose restart
```

#### Database connection issues
```bash
# Check database container
docker-compose exec db mysql -u wp -p

# Check WordPress container
docker-compose exec wordpress wp --info
```

#### Port conflicts
```bash
# Check if port 8080 is available
netstat -tulpn | grep 8080

# Change port in docker-compose.yml if needed
ports:
  - "8081:80"  # Change to 8081 or other available port
```

## 📊 Development Workflow

### Setting Up Development Environment

#### Step 1: Clone the Repository
```bash
git clone https://github.com/YOUR_USERNAME/html-to-wp-importer.git
cd html-to-wp-importer
```

#### Step 2: Create Development Branch
```bash
git checkout -b dev/your-feature-name
```

#### Step 3: Set Up Docker Environment
```bash
# Start Docker containers
docker-compose up -d

# Wait for WordPress to be ready
docker-compose exec wordpress wp --info
```

#### Step 4: Install Plugin in Development Mode
```bash
# The plugin is already mounted via Docker volume
# Changes are immediately available in the container
```

#### Step 5: Enable WordPress Debug Mode
```bash
# Edit wp-config.php in the container
docker-compose exec wordpress wp config set WP_DEBUG true --raw
docker-compose exec wordpress wp config set WP_DEBUG_LOG true --raw
docker-compose exec wordpress wp config set WP_DEBUG_DISPLAY false --raw
```

### Making Changes

#### Step 1: Make Your Changes
```bash
# Edit files in the wp-content/plugins/html-to-wp-importer/ directory
# Changes are immediately available in the Docker container
```

#### Step 2: Test Your Changes
```bash
# Test in WordPress admin and verify functionality
# Open http://localhost:8080/wp-admin in your browser
```

#### Step 3: Commit Changes
```bash
git add .
git commit -m "Describe your changes"
```

#### Step 4: Push to Repository
```bash
git push origin dev/your-feature-name
```

#### Step 5: Create Pull Request
1. **Visit GitHub**: Go to your repository on GitHub
2. **Create Pull Request**: Click "Compare & pull request"
3. **Select Branches**: Choose your feature branch
4. **Add Description**: Describe your changes
5. **Submit PR**: Click "Create pull request"

### Code Quality

#### Check PHP Syntax
```bash
# Check all PHP files for syntax errors
find wp-content/plugins/html-to-wp-importer/ -name "*.php" -exec php -l {} \;
```

#### Check WordPress Coding Standards
```bash
# Install WordPress Coding Standards first
composer require wp-coding-standards/wpcs

# Check code standards
./vendor/bin/phpcs --standard=WordPress wp-content/plugins/html-to-wp-importer/includes/
```

#### Run Tests
```bash
# If PHPUnit is set up
./vendor/bin/phpunit
```

## 🔄 Deployment

### Creating Release

#### Step 1: Update Version Numbers
```bash
# Edit html-to-wp-importer.php
# Update PROJECT_MANIFEST.md
# Update README.md version information
```

#### Step 2: Create Release ZIP
```bash
# Create plugin ZIP for distribution
cd html-to-wp-importer
zip -r html-to-wp-importer-v1.0.zip wp-content/plugins/html-to-wp-importer/
```

#### Step 3: Test Release ZIP
```bash
# Install in fresh WordPress installation
# Test all functionality
# Verify no errors occur
```

#### Step 4: Tag Release
```bash
git tag -a v1.0 -m "Version 1.0 Release"
git push origin v1.0
```

### Updating Production

#### Step 1: Backup Current Installation
```bash
# Backup current plugin files
cp -r /var/www/wordpress/wp-content/plugins/html-to-wp-importer /backup/

# Backup database
mysqldump -u user -p database > backup.sql
```

#### Step 2: Deploy New Version
```bash
# Upload new ZIP via WordPress admin
# Or use WP-CLI
wp plugin update html-to-wp-importer
```

#### Step 3: Verify Functionality
```bash
# Test import functionality
# Check for errors
# Verify all features work
```

## 📚 Additional Resources

### Documentation
- **README.md**: Project overview and usage instructions
- **PROJECT_MANIFEST.md**: Development roadmap and status
- **docs/DEPENDENCIES.md**: System requirements and technical specs
- **docs/SETUP.md**: Installation and configuration guide
- **GITHUB_SETUP.md**: GitHub repository setup instructions

### Support
- **GitHub Issues**: Report bugs and request features
- **WordPress.org Forums**: Community support
- **Email Support**: For critical issues

### Development Tools
- **Docker**: Local development environment
- **WP-CLI**: Command-line WordPress management
- **Query Monitor**: Debug and optimize WordPress
- **Debug Bar**: WordPress debugging toolbar

---

## 🎯 Summary

The HTML to WordPress Importer plugin is now fully set up and ready for development and deployment. This guide provides comprehensive instructions for:

- **Quick setup** with Docker or manual installation
- **Configuration** of PHP, WordPress, and server settings
- **Testing procedures** for functionality and performance
- **Troubleshooting** common issues
- **Development workflow** for contributors
- **Deployment procedures** for production use

**Key Features Implemented:**
- Complete HTML to WordPress conversion
- Advanced asset management and URL rewriting
- Elementor integration for block editing
- Background processing for large imports
- Comprehensive admin interface
- Security and error handling

**Backup Information:**
- **Backup Created**: `instant-html-to-wp-backup-20250831-172010.tar.gz`
- **Repository**: `https://github.com/YOUR_USERNAME/html-to-wp-importer`
- **Branch**: `feature-complete-v1.0`

The plugin is now ready for production use and continued development.