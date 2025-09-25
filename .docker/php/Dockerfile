# PHP-FPM for Symfony
FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions commonly needed by Symfony
RUN set -eux; \
    apk add --no-cache \
        bash \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
        libpng-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        postgresql-dev \
        git \
        unzip \
        alpine-sdk \
        autoconf \
        automake \
        make \
        libtool \
        rabbitmq-c-dev; \
    \
    pecl install amqp \
        redis; \
    docker-php-ext-enable amqp \
        redis; \
    docker-php-ext-configure gd \
        --with-jpeg=/usr/include/ \
        --with-freetype=/usr/include/; \
    docker-php-ext-install -j"$(nproc)" \
        intl \
        opcache \
        zip \
        gd \
        pdo \
        pdo_pgsql \
        mbstring; \
    \
    # Recommended settings for Symfony dev
    { \
      echo "memory_limit=512M"; \
      echo "upload_max_filesize=50M"; \
      echo "post_max_size=50M"; \
      echo "date.timezone=UTC"; \
    } > /usr/local/etc/php/conf.d/symfony.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Ensure proper permissions for cache/logs in dev
RUN addgroup -g 1000 app && adduser -D -G app -u 1000 app \
    && chown -R app:app /var/www/html

USER app
