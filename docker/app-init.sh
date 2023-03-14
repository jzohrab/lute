#!/bin/bash

# Check if the APP_ENV parameter is set or not
if [ -z "$APP_ENV" ]; then
  echo "APP_ENV parameter is missing!"
  exit 1
fi

cd /var/www/html && \
    composer run-script db:setup:$APP_ENV && \
    composer run-script db:migrate:$APP_ENV
