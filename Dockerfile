FROM php:8.3-apache

# Copy the rest of the application code and configuration files
COPY . /var/www/html/
COPY /.tools/default.conf /etc/apache2/sites-available/000-default.conf
COPY /.tools/php.ini /usr/local/etc/php/php.ini
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Install system dependencies, configure PHP extensions, and perform cleanup
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       curl \
       dialog \
       libicu-dev \
       libssl-dev \
       openssh-server \
       sed \
       unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl pdo_mysql mysqli \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf \
    && echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && touch /var/log/php_errors.log \
    && chown www-data:www-data /var/log/php_errors.log \
    && chmod 644 /var/log/php_errors.log \
    && chown www-data:www-data /var/tmp \
    && chown www-data:www-data /var/www/html/.tools \
    && chown www-data:www-data /var/www/html/public/assets/images/profile \
    && chmod 755 /var/www/html \
    && chmod 1733 /var/tmp \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer \
    && composer --version \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && if [ -f vendor/erusev/parsedown/Parsedown.php ]; then \
        sed -i "s/\$class = 'language-'.\$language;/\$class = 'language-'.\$language . ' c0py';/g" vendor/erusev/parsedown/Parsedown.php; \
    else \
        echo "File vendor/erusev/parsedown/Parsedown.php not found"; \
    fi \
    && a2enmod rewrite \
    && a2enmod headers \
    && rm -rf /var/lib/apt/lists/* \
    && service apache2 restart

# Set the entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Expose ports (for documentation purposes)
EXPOSE 22
EXPOSE 80