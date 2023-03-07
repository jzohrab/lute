FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y libonig-dev zip unzip

# Install & Enable PHP extensions
RUN docker-php-ext-install ctype iconv mbstring mysqli pdo_mysql
RUN docker-php-ext-enable ctype iconv mbstring mysqli pdo_mysql
RUN a2enmod headers rewrite

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

ENV COMPOSER_ALLOW_SUPERUSER=1

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy existing application directory contents
COPY ../. /var/www/html


# Ensure we can log and cache
RUN mkdir -p /var/www/html/var
RUN chown -R www-data:www-data /var/www/html/

# Set file permissions
RUN chmod -R 755 /var/www/html/public

# Install composer dependencies
RUN composer install --ignore-platform-reqs

#apache setup
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# replace documentroot in apache config
RUN sed -i 's/\/var\/www\/html/\/var\/www\/html\/public/g' /etc/apache2/sites-available/000-default.conf
RUN rm /etc/apache2/sites-enabled/000-default.conf && \
    ln -s /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-enabled/000-default.conf && \
    a2ensite 000-default.conf

EXPOSE 8080
CMD ["apachectl", "-D", "FOREGROUND"]
