FROM php:8.2

# Install mecab for Japanese support
RUN apt-get update -y
  && apt-get install -y mecab mecab-ipadic-utf8
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer dependencies
RUN composer install --no-dev

COPY . /usr/src/lute
COPY .env.example /usr/src/lute/.env
WORKDIR /usr/src/lute

CMD ["php", "-S", "0.0.0.0:8000"]