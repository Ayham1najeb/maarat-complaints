# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy all project files
COPY . .

# Run post-autoload scripts
RUN composer dump-autoload --optimize

# Create necessary directories if they don't exist
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Fix permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/public

# Verify index.php exists (for debugging)
RUN ls -la /var/www/html/public/index.php || echo "WARNING: index.php not found!"

# Copy Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Set ServerName to suppress warning
RUN echo "ServerName maarat-complaints.onrender.com" >> /etc/apache2/apache2.conf

# Enable Apache modules
RUN a2enmod headers

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
