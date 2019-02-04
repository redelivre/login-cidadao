FROM php:7.1-apache

RUN a2enmod rewrite

# Instal OS dependencies
RUN apt-get -y update
RUN apt-get install -y zlibc zlib1g-dev libxml2-dev libicu-dev libpq-dev nodejs zip unzip git libz-dev

# Install PHP dependencies
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
 && docker-php-ext-install pdo pdo_pgsql pdo_mysql intl soap \
 && docker-php-ext-enable pdo pdo_mysql pdo_pgsql intl soap

# Create link to nodejs
RUN ln -s /usr/bin/nodejs /usr/bin/node

# Install XDebug
RUN yes | pecl install xdebug-2.5.5

# Configure XDebug
RUN echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
 && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

# Configure PHP and Apache
RUN echo "date.timezone = America/Sao_Paulo" > /usr/local/etc/php/conf.d/php-timezone.ini \
 && echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory_limit.ini \
 && echo "<VirTualHost *:80>" > /etc/apache2/conf-enabled/lc-docroot.conf \
 && echo "    DocumentRoot /var/www/html/web" >> /etc/apache2/conf-enabled/lc-docroot.conf \
 && echo "</VirtualHost>" >> /etc/apache2/conf-enabled/lc-docroot.conf \
 && echo "<IfModule mod_ssl.c> " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "  <VirtualHost _default_:443> " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "      DocumentRoot /var/www/html/web " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "      SSLEngine on " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "      SSLCertificateFile  /etc/ssl/certs/ssl-cert-snakeoil.pem " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "      SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "      <FilesMatch \"\.(cgi|shtml|phtml|php)$\"> " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "          SSLOptions +StdEnvVars " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "      </FilesMatch> " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "      <Directory /usr/lib/cgi-bin> " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "          SSLOptions +StdEnvVars " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "      </Directory> " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "  </VirtualHost> " >> /etc/apache2/conf-enabled/lc-docroot.conf  \
 && echo "</IfModule> " >> /etc/apache2/conf-enabled/lc-docroot.conf

# Apache ssl
RUN openssl req -new -x509 -nodes -days 365 -newkey rsa:4096 \
 -keyout /etc/ssl/private/ssl-cert-snakeoil.key \
 -out /etc/ssl/certs/ssl-cert-snakeoil.crt \
 -subj "/C=BR/ST=PR/L=CURITIBA/O=Local/CN=localhost" \
 && cat /etc/ssl/private/ssl-cert-snakeoil.key /etc/ssl/certs/ssl-cert-snakeoil.crt > /etc/ssl/certs/ssl-cert-snakeoil.pem \
 && a2enmod ssl

WORKDIR /var/www/html
# Instal composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php

# Instal composer dependencies
COPY ./composer.* /var/www/html/
RUN php composer.phar config cache-dir
RUN php composer.phar install --no-interaction --no-scripts --no-autoloader
COPY . /var/www/html
RUN php composer.phar dump-autoload -d /var/www/html
RUN chown -R www-data /var/www/html/app/logs /var/www/html/app/cache /var/www/html/web/uploads /var/www/html/app/config/jwks
# RUN php app/console assets:install \
#  && php app/console assets:install -e prod \
#  && php app/console assetic:dump -e prod
