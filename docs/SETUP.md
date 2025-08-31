# Setup and Configuration Guide

## Quick Start Guide

### Prerequisites
Before installing the HTML to WordPress Importer plugin, ensure you have:
- WordPress 5.0+ installed and running
- PHP 7.0+ with required extensions
- Proper file permissions for WordPress directories
- Administrative access to WordPress admin

### Installation Steps

#### Method 1: WordPress Admin (Recommended)
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

#### Method 2: Manual Installation
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

#### Method 3: Command Line (WP-CLI)
```bash
# Install plugin via WP-CLI
wp plugin install /path/to/html-to-wp-importer.zip --activate

# Or if already uploaded
wp plugin activate html-to-wp-importer
```

## Configuration

### Basic Configuration
1. **Access the Plugin**
   - Go to WordPress Admin → **HTML Importer**
   - You'll see the main import interface

2. **Configure Upload Settings**
   - **File Size Limits**: Ensure your PHP settings allow uploads up to 50MB
   - **File Types**: Plugin accepts .html, .htm, and .zip files
   - **Upload Directory**: Plugin uses WordPress default upload directory

### Advanced Configuration

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

## User Setup

### User Permissions
The plugin requires specific WordPress capabilities:
- **upload_files**: For uploading HTML/ZIP files
- **publish_pages**: For creating WordPress pages
- **manage_options**: For accessing plugin settings (admin only)
- **edit_theme_options**: For managing navigation menus

### Creating User Roles
```php
// Add to your theme's functions.php or a custom plugin
add_role('html_importer', 'HTML Importer', array(
    'read' => true,
    'upload_files' => true,
    'publish_pages' => true,
    'edit_theme_options' => true,
));
```

## Directory Structure Setup

### Required Directories
The plugin will create these directories automatically:
```
/wp-content/
├── plugins/
│   └── html-to-wp-importer/     # Plugin files
├── uploads/
│   └── html-importer-assets/    # Imported assets
└── themes/                      # Your active theme
```

### Manual Directory Creation
If automatic creation fails, create directories manually:
```bash
# Create upload directory
mkdir -p /var/www/wordpress/wp-content/uploads/html-importer-assets

# Set proper permissions
chown -R www-data:www-data /var/www/wordpress/wp-content/uploads/
chmod -R 755 /var/www/wordpress/wp-content/uploads/
```

## Testing the Installation

### Basic Functionality Test
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
   - Upload your test HTML file
   - Verify the page is created and assets are properly linked

4. **Test ZIP Import**
   ```bash
   # Create ZIP file
   zip -r test-import.zip test-import/
   ```
   - Upload the ZIP file
   - Verify all pages are created and assets work

### Advanced Testing
1. **Large File Testing**
   - Test with larger HTML files (1MB+)
   - Test with ZIP files containing multiple pages
   - Verify performance and memory usage

2. **Error Handling Testing**
   - Upload invalid file types
   - Upload corrupted ZIP files
   - Test with insufficient permissions

3. **Theme Compatibility**
   - Test with different WordPress themes
   - Verify navigation menu integration
   - Check responsive design compatibility

## Troubleshooting Common Issues

### Installation Issues

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

### Upload Issues

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

### Asset Issues

**Assets not copied correctly**
```bash
# Check upload directory permissions
ls -la /var/www/wordpress/wp-content/uploads/

# Create directory manually if needed
mkdir -p /var/www/wordpress/wp-content/uploads/html-importer-assets
chown www-data:www-data /var/www/wordpress/wp-content/uploads/html-importer-assets
chmod 755 /var/www/wordpress/wp-content/uploads/html-importer-assets
```

**Asset URLs not rewritten**
```php
// Enable debug mode to see errors
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check browser console for 404 errors
// Verify asset URLs in page source
```

## Performance Optimization

### Server Optimization
```bash
# PHP-FPM optimization (php-fpm.conf)
pm = dynamic
pm.max_children = 50
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3

# MySQL optimization (my.cnf)
innodb_buffer_pool_size = 256M
query_cache_size = 64M
max_allowed_packet = 256M
```

### WordPress Optimization
```php
// Add to wp-config.php
define('WP_CACHE', true);
define('WP_MEMORY_LIMIT', '256M');

// Install caching plugins
// Recommended: WP Rocket, W3 Total Cache, or WP Super Cache
```

### Content Delivery Network (CDN)
```php
// Add CDN support in wp-config.php
define('WP_CONTENT_URL', 'https://cdn.your-domain.com/wp-content');
```

## Security Configuration

### File Permissions
```bash
# Set secure permissions
find /var/www/wordpress -type d -exec chmod 755 {} \;
find /var/www/wordpress -type f -exec chmod 644 {} \;

# Special permissions for wp-config.php
chmod 600 /var/www/wordpress/wp-config.php
```

### WordPress Security
```php
// Add to wp-config.php
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);
define('FORCE_SSL_ADMIN', true);
define('WP_HTTP_BLOCK_EXTERNAL', true);
```

### Server Security
```apache
# Add to .htaccess
# Block access to sensitive files
<FilesMatch "^.*(error_log|wp-config\.php|php\.ini|\.htaccess)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes
```

## Backup and Recovery

### Backup Script
```bash
#!/bin/bash
# backup.sh - Complete WordPress backup

BACKUP_DIR="/backups/wordpress"
DATE=$(date +%Y%m%d_%H%M%S)
SITE_URL="your-domain.com"

# Create backup directory
mkdir -p "$BACKUP_DIR/$DATE"

# Backup database
wp db export "$BACKUP_DIR/$DATE/database.sql" --path=/var/www/wordpress

# Backup files
tar -czf "$BACKUP_DIR/$DATE/files.tar.gz" \
    /var/www/wordpress/wp-content/plugins/ \
    /var/www/wordpress/wp-content/themes/ \
    /var/www/wordpress/wp-content/uploads/

# Keep only 30 days of backups
find "$BACKUP_DIR" -type d -mtime +30 -exec rm -rf {} \;

echo "Backup completed: $BACKUP_DIR/$DATE"
```

### Recovery Script
```bash
#!/bin/bash
# restore.sh - Restore WordPress from backup

BACKUP_DIR="/backups/wordpress/$1"
WP_PATH="/var/www/wordpress"

# Restore database
wp db import "$BACKUP_DIR/database.sql" --path="$WP_PATH"

# Restore files
tar -xzf "$BACKUP_DIR/files.tar.gz" -C "$WP_PATH/wp-content/"

# Set proper permissions
chown -R www-data:www-data "$WP_PATH/wp-content/"

echo "Restore completed from: $BACKUP_DIR"
```

## Monitoring and Maintenance

### Log Monitoring
```bash
# Monitor WordPress error logs
tail -f /var/log/wordpress/debug.log

# Monitor PHP error logs
tail -f /var/log/php_errors.log

# Monitor access logs
tail -f /var/log/nginx/access.log
```

### Performance Monitoring
```bash
# Monitor server resources
htop
df -h
free -m

# Monitor MySQL performance
mysqladmin processlist
mysqladmin status
```

### Automated Health Check
```bash
#!/bin/bash
# health-check.sh - Plugin health monitoring

PLUGIN_DIR="/var/www/wordpress/wp-content/plugins/html-to-wp-importer"
UPLOAD_DIR="/var/www/wordpress/wp-content/uploads/html-importer-assets"

# Check if plugin directory exists
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "ERROR: Plugin directory not found"
    exit 1
fi

# Check if upload directory is writable
if [ ! -w "$UPLOAD_DIR" ]; then
    echo "ERROR: Upload directory not writable"
    exit 1
fi

# Check PHP extensions
php -m | grep -q zip || echo "WARNING: ZIP extension not enabled"
php -m | grep -q dom || echo "WARNING: DOM extension not enabled"

# Check WordPress status
wp plugin status html-to-wp-importer --path=/var/www/wordpress

echo "Health check completed"
```

---

This comprehensive setup guide ensures that the HTML to WordPress Importer plugin can be properly installed, configured, and maintained in various environments.