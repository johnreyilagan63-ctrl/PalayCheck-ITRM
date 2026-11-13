FROM php:8.3-apache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libonig-dev libpq-dev \
    && docker-php-ext-install curl mbstring pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
        storage/logs storage/app/private storage/app/public bootstrap/cache public/static/uploads \
    && chown -R www-data:www-data storage bootstrap/cache public/static/uploads \
    && chmod -R ug+rwX storage bootstrap/cache public/static/uploads \
    && install -m 755 /var/www/html/start-render.sh /usr/local/bin/start-render.sh

CMD ["/usr/local/bin/start-render.sh"]
