FROM php:8.3-fpm

ARG user=ktc
ARG uid=1000

RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev libwebp-dev libxpm-dev \
    fontconfig fonts-liberation libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user

WORKDIR /var/www/html
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

RUN mkdir -p var/cache var/log public/uploads \
    && chown -R $user:www-data var public/uploads \
    && chmod -R 775 var public/uploads

COPY . /var/www/html

USER $user

CMD ["php", "-S", "0.0.0.0:8010", "-t", "public"]
