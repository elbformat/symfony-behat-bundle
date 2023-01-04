FROM php:8.2-alpine
# For codecov upload inside circleci
RUN apk add gpg gpg-agent gpgv
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_MEMORY_LIMIT=-1
WORKDIR /var/www