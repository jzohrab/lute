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

ENV APP_ENV=prod

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

WORKDIR /

COPY ./composer.* ./

# Composer dependencies.
# --no-scripts was necessary because otherwise the build failed with:
#   Executing script cache:clear [KO]
#   Script cache:clear returned with error code 1
#   !!  Could not open input file: ./bin/console
RUN APP_ENV=prod composer install --no-dev --no-scripts

COPY . .
# COPY .env.example ./.env

WORKDIR public
CMD ["php", "-S", "0.0.0.0:8000"]
EXPOSE 8000