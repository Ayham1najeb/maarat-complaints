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

# Copy all project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Fix permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

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
