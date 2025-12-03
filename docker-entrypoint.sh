#!/bin/bash
set -e

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed database with initial data
echo "Seeding database..."
php artisan db:seed --force

# Create storage link
echo "Creating storage link..."
php artisan storage:link

# Cache configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Apache
echo "Starting Apache..."
exec apache2-foreground
