# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Enable Apache rewrite module and headers
RUN a2enmod rewrite headers

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (as root)
COPY --chown=www-data:www-data composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy all project files with correct ownership
COPY --chown=www-data:www-data . .

# Run post-autoload scripts
RUN composer dump-autoload --optimize

# Create necessary directories if they don't exist
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod 644 /var/www/html/public/index.php

# Verify index.php exists and has correct permissions
RUN ls -la /var/www/html/public/index.php && echo "index.php found with correct permissions"

# Copy Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Set ServerName to suppress warning
RUN echo "ServerName maarat-complaints.onrender.com" >> /etc/apache2/apache2.conf

# Copy and set permissions for entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 80
EXPOSE 80

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

