FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mbstring pdo_mysql \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html

RUN mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage

COPY docker/entrypoint.sh /usr/local/bin/producers-entrypoint
RUN chmod +x /usr/local/bin/producers-entrypoint

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1/") === false ? 1 : 0);'

ENTRYPOINT ["producers-entrypoint"]
CMD ["apache2-foreground"]
