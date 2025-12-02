# Use PHP 8.2 with Apache
FROM php:8.2-apache
# Version: 2.0 - Fixed index.php deployment issue

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring bcmath gd zip xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy everything first
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Create storage directories
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Set ownership and permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copy Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Configure Apache
RUN echo "ServerName maarat-complaints.onrender.com" >> /etc/apache2/apache2.conf

# Debug: List public directory contents and verify index.php
RUN echo "=== Contents of /var/www/html/public ===" \
    && ls -la /var/www/html/public/ \
    && echo "=== End of listing ===" \
    && if [ -f /var/www/html/public/index.php ]; then \
        echo "✓ index.php found!"; \
        chmod 644 /var/www/html/public/index.php; \
        echo "✓ Permissions set to 644"; \
    else \
        echo "✗ ERROR: index.php NOT found!"; \
        exit 1; \
    fi

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]

