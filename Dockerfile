FROM php:8.2

# Install mecab for Japanese support
RUN apt-get update -y \
  && apt-get install -y mecab mecab-ipadic-utf8 \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Other tools
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

WORKDIR /usr/src/myapp

COPY ./composer.* ./


# Composer dependencies
RUN composer install --no-dev

COPY . .
COPY .env.example .

CMD ["php", "-S", "0.0.0.0:8000"]