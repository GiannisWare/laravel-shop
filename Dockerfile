
FROM php:8.5-apache-bookworm AS php-base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install --no-install-recommends --yes \
        git \
        unzip \
        libavif-bin \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libpng-dev \
        libpq-dev \
        libwebp-dev \
        libzip-dev \
        gifsicle \
        jpegoptim \
        optipng \
        pngquant \
        webp \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pdo_pgsql \
        zip \
    && pecl install imagick-3.8.1 \
    && docker-php-ext-enable imagick \
    && a2enmod expires headers rewrite \
    && sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY docker/apache.conf /etc/apache2/conf-available/laravel.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-kokkonis.ini

RUN a2enconf laravel

WORKDIR /var/www/html

FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
RUN npm run build

FROM php-base AS runner

ENV APP_ENV=production \
    APP_DEBUG=false

COPY --from=vendor /var/www/html /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

RUN mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/media-library/temp \
    && chown -R www-data:www-data bootstrap/cache storage

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl --fail --silent http://127.0.0.1/up > /dev/null || exit 1

EXPOSE 80

CMD ["apache2-foreground"]
