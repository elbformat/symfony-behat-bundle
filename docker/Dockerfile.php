FROM php:7.4-alpine
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_MEMORY_LIMIT=-1
WORKDIR /var/www