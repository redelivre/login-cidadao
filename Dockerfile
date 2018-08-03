# See https://github.com/docker-library/php/blob/master/7.1/fpm/Dockerfile
FROM redelivre/php

WORKDIR /var/www/html

# Instal composer dependencies
COPY ./composer.* /var/www/html/
RUN composer config cache-dir
RUN composer install --no-interaction --no-scripts --no-autoloader
COPY . /var/www/html
RUN composer dump-autoload -d /var/www/html
RUN chown -R www-data /var/www/html
