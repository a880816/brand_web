FROM php:8.4-fpm-alpine

RUN apk add --no-cache postgresql-dev libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql gd \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint
ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]
