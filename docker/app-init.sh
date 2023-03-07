#!/bin/bash

# todo: replace prod with $1 and pass in from docker-compose.yml from APP_ENV

cd /var/www/html && \
    composer run-script db:setup:prod && \
    composer run-script db:migrate:prod