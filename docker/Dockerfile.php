FROM php:8.2-alpine

# For clock-mock
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS &&\
    pecl install uopz-7.1.1 &&\
    apk del .build-deps &&\
    docker-php-ext-enable uopz

# For code coverage
RUN apk add --no-cache --virtual .build-deps autoconf g++ make && \
    pecl install pcov-1.0.11 && \
    apk del .build-deps && \
    rm -rf /tmp/* &&\
    docker-php-ext-enable pcov

# For debugging
RUN apk add --no-cache --virtual .build-deps autoconf g++ make linux-headers &&\
    pecl install xdebug-3.2.0 && \
    apk del .build-deps && \
    rm -rf /tmp/*

# For codecov upload inside circleci
RUN apk add gpg gpg-agent gpgv
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_MEMORY_LIMIT=-1
WORKDIR /var/www