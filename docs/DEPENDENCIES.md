# Dependencies and Technical Requirements

## System Requirements

### Minimum Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.0 or higher
- **MySQL**: 5.6 or higher
- **Memory**: 64MB PHP memory limit
- **Disk Space**: 10MB for plugin + additional space for imported assets

### Recommended Requirements
- **WordPress**: 6.0 or higher
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Memory**: 128MB PHP memory limit
- **Disk Space**: 50MB+ for plugin and imported assets

### Docker Requirements
- **Docker**: 20.0 or higher
- **Docker Compose**: 1.29 or higher
- **Available Ports**: 8080 (for WordPress access)

## PHP Extensions

### Required Extensions
- **ZipArchive**: For ZIP file processing
- **DOMDocument**: For HTML parsing
- **Fileinfo**: For file type detection
- **JSON**: For WordPress compatibility
- **MBString**: For string handling
- **cURL**: For WordPress updates and communication

### Optional Extensions
- **GD Library**: For image processing
- **Imagick**: For advanced image processing
- **XML**: For enhanced XML handling
- **SOAP**: For web service integration

## WordPress Configuration

### Required WordPress Settings
```php
// wp-config.php settings
define('WP_DEBUG', false); // Disable debug in production
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

// Ensure file uploads are enabled
define('ALLOW_UNFILTERED_UPLOADS', false); // Security best practice
```

### Recommended PHP Settings
```php
// php.ini settings
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 128M
max_execution_time = 300
max_input_time = 300
```

### WordPress Permissions
- **File Uploads**: Users need `upload_files` capability
- **Page Creation**: Users need `publish_pages` capability
- **Plugin Management**: Users need `manage_options` capability
- **Menu Management**: Users need `edit_theme_options` capability

## Server Configuration

### Apache Configuration
```apache
# .htaccess settings for WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>

# Increase upload limits
<IfModule mod_php.c>
    php_value upload_max_filesize 50M
    php_value post_max_size 50M
    php_value memory_limit 128M
</IfModule>
```

### Nginx Configuration
```nginx
# nginx.conf settings
client_max_body_size 50M;
fastcgi_read_timeout 300s;

# WordPress location block
location / {
    try_files $uri $uri/ /index.php?$args;
}

# PHP processing
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param PHP_VALUE "upload_max_filesize=50M \n post_max_size=50M \n memory_limit=128M";
}
```

## Database Requirements

### MySQL Configuration
```sql
-- Recommended MySQL settings
SET GLOBAL max_allowed_packet = 256M;
SET GLOBAL innodb_buffer_pool_size = 256M;
SET GLOBAL query_cache_size = 64M;
```

### Database Tables
The plugin creates no additional database tables. It uses standard WordPress:
- `wp_posts`: For storing imported pages
- `wp_postmeta`: For page metadata
- `wp_options`: For plugin settings
- `wp_terms`: For navigation menu taxonomy
- `wp_term_relationships`: For menu-item relationships
- `wp_term_taxonomy`: For menu taxonomy

## File System Requirements

### Directory Permissions
```
wp-content/
├── plugins/          (755) - Plugin installation directory
├── uploads/          (755) - File upload directory
│   └── html-importer-assets/ (755) - Imported assets
└── themes/           (755) - Theme directory
```

### File Permissions
- Plugin files: 644 (readable by web server)
- Directories: 755 (executable by web server)
- Upload directory: 755 (writable by web server)

## Browser Compatibility

### Supported Browsers
- **Chrome**: 60+
- **Firefox**: 55+
- **Safari**: 12+
- **Edge**: 79+
- **Opera**: 47+

### Mobile Browsers
- **Safari Mobile**: 12+
- **Chrome Mobile**: 60+
- **Firefox Mobile**: 55+

## Docker Environment Variables

### Required Variables
```yaml
environment:
  WORDPRESS_DB_HOST: db:3306
  WORDPRESS_DB_USER: wp
  WORDPRESS_DB_PASSWORD: wp
  WORDPRESS_DB_NAME: wordpress
```

### Optional Variables
```yaml
environment:
  WORDPRESS_DEBUG: 1
  WORDPRESS_CONFIG_EXTRA: |
    define('WP_DEBUG', true);
    define('WP_DEBUG_LOG', true);
```

## Plugin Dependencies

### Required Plugins
- **None**: The plugin works with standard WordPress installation

### Optional Plugins
- **Elementor**: For Elementor block conversion
- **Action Scheduler**: For background processing of large imports

### WordPress Version Compatibility
- **WordPress 5.0+**: Full compatibility
- **WordPress 6.0+**: Recommended for best performance
- **WordPress 6.3+**: Tested with Docker environment

## PHP Version Compatibility

### Supported PHP Versions
- **PHP 7.0**: Minimum supported version
- **PHP 7.1-7.4**: Full compatibility
- **PHP 8.0-8.3**: Recommended for best performance
- **PHP 8.4+**: Compatibility testing needed

### Deprecated Features
- **PHP 5.x**: Not supported
- **MySQL 5.5**: Not supported
- **Legacy WordPress functions**: Avoided for compatibility

## Security Requirements

### PHP Security Settings
```php
// Security recommendations in php.ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
allow_url_fopen = Off
allow_url_include = Off
expose_php = Off
display_errors = Off
log_errors = On
```

### WordPress Security
```php
// Security recommendations in wp-config.php
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);
define('FORCE_SSL_ADMIN', true);
define('WP_HTTP_BLOCK_EXTERNAL', true);
```

### File Upload Security
- Maximum file size: 50MB
- Allowed file types: .html, .htm, .zip, .tar.gz
- Virus scanning: Recommended but not implemented
- File validation: Strict MIME type checking

## Performance Requirements

### Server Resources
- **CPU**: Single core minimum, 2+ cores recommended
- **RAM**: 1GB minimum, 2GB+ recommended
- **Disk**: SSD recommended for better I/O performance
- **Network**: Stable internet connection for updates

### Caching Recommendations
- **Object Caching**: Redis or Memcached recommended
- **Page Caching**: WP Rocket, W3 Total Cache, or similar
- **Browser Caching**: Leverage browser caching for assets
- **CDN**: Content Delivery Network for asset delivery

## Development Environment

### Local Development Setup
```bash
# Recommended local development tools
- Docker and Docker Compose
- Local by Flywheel
- XAMPP
- MAMP
- Vagrant with VVV
```

### Development Tools
```bash
# Code quality tools
- PHP_CodeSniffer
- WordPress Coding Standards
- ESLint for JavaScript
- Stylelint for CSS

# Testing tools
- PHPUnit
- WP-CLI
- Browser testing tools
```

## Environment Variables

### Required Environment Variables
```bash
# WordPress environment
WP_ENV=production
WP_HOME=https://your-domain.com
WP_SITEURL=https://your-domain.com

# Database
DB_NAME=wordpress_db
DB_USER=wordpress_user
DB_PASSWORD=secure_password
DB_HOST=localhost

# Security
AUTH_KEY=your_auth_key
SECURE_AUTH_KEY=your_secure_auth_key
LOGGED_IN_KEY=your_logged_in_key
NONCE_KEY=your_nonce_key
AUTH_SALT=your_auth_salt
SECURE_AUTH_SALT=your_secure_auth_salt
LOGGED_IN_SALT=your_logged_in_salt
NONCE_SALT=your_nonce_salt
```

### Optional Environment Variables
```bash
# Performance
WP_CACHE=true
WP_REDIS_HOST=localhost
WP_REDIS_PORT=6379

# Debugging
WP_DEBUG=false
WP_DEBUG_LOG=false
WP_DEBUG_DISPLAY=false
SCRIPT_DEBUG=false
```

## Monitoring and Logging

### Error Logging
```php
// Configure error logging in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_LOG_DIR', '/var/log/wordpress/');
```

### Monitoring Recommendations
- **Server Monitoring**: CPU, memory, disk usage
- **Application Monitoring**: Error rates, response times
- **Database Monitoring**: Query performance, connection counts
- **Security Monitoring**: Failed login attempts, file changes

## Backup Requirements

### Backup Strategy
- **Database**: Daily backups with 30-day retention
- **Files**: Weekly backups with 30-day retention
- **Plugin Files**: Version control with Git
- **Imported Assets**: Include in file backups

### Backup Tools
- **WordPress Plugins**: UpdraftPlus, BackupBuddy, VaultPress
- **Server Level**: rsync, tar, mysqldump
- **Cloud Storage**: AWS S3, Google Cloud Storage, Backblaze

## Compliance and Legal

### Data Privacy
- GDPR compliance for user data handling
- CCPA compliance for California residents
- Data processing agreements for third-party services

### Licensing
- WordPress GPL v2 or later compatibility
- Third-party library licensing compliance
- Open source license requirements

## Testing Requirements

### Testing Environment
- **Staging Server**: Mirror of production environment
- **Test Data**: Sample HTML files and ZIP archives
- **User Accounts**: Various user roles for testing
- **Browser Testing**: Cross-browser compatibility testing

### Testing Types
- **Unit Testing**: Individual component testing
- **Integration Testing**: Component interaction testing
- **End-to-End Testing**: Complete workflow testing
- **Performance Testing**: Load and stress testing
- **Security Testing**: Vulnerability assessment

---

This document provides comprehensive technical requirements for deploying and maintaining the HTML to WordPress Importer plugin in various environments.