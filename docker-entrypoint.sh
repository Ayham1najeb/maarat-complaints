#!/bin/bash
set -e

# Create necessary directories
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Set permissions
chown -R www-data:www-data /var/www/html
find /var/www/html -type f -exec chmod 644 {} \;
find /var/www/html -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Verify index.php exists
if [ ! -f /var/www/html/public/index.php ]; then
    echo "ERROR: index.php not found!"
    exit 1
fi

echo "Starting Apache..."
exec apache2-foreground
