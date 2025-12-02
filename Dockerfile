# Use official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring bcmath gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy Laravel project to Apache server
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Give permissions to storage and bootstrap
RUN chmod -R 777 storage bootstrap/cache

# Set Laravel env to production
ENV APP_ENV=production
ENV APP_DEBUG=false

# Expose port
EXPOSE 80

CMD ["apache2-foreground"]
