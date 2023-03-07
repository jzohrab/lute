FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y libonig-dev zip unzip

# Install & Enable PHP extensions
RUN docker-php-ext-install ctype iconv mbstring mysqli pdo_mysql
RUN docker-php-ext-enable  ctype iconv mbstring mysqli pdo_mysql
RUN a2enmod headers rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Ensure we can log and cache
RUN chown -R www-data:www-data /var/www/html/var

# Install composer dependencies
RUN composer install

# replace documentroot in apache config
RUN sed -i 's/\/var\/www\/html/\/var\/www\/html\/public/g' /etc/apache2/sites-enabled/000-default.conf

EXPOSE 80
CMD ["apachectl", "-D", "FOREGROUND"]
