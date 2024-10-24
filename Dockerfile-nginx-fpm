# Use PHP-FPM base image with Nginx included
FROM php:8.3-fpm

# Install Nginx and additional system dependencies
RUN apt-get update \
    && apt-get install -y nginx \
                          libicu-dev \
                          libssl-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions and configure PHP
RUN docker-php-ext-install pdo_mysql mysqli

# Copy Nginx configuration file
COPY config/nginx.conf /etc/nginx/nginx.conf

# Copy PHP configuration file
COPY config/php.ini /usr/local/etc/php/php.ini

# Copy application files to web root
COPY . /var/www/html/

# Set up permissions
#RUN chown -R www-data:www-data /var/www/html

# Start PHP-FPM
CMD service php-fpm
