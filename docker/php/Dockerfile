FROM php:8.2-fpm

# Arguments
ARG user=ktc
ARG uid=1000

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    zip \
    unzip \
    wkhtmltopdf \
    xvfb \
    fontconfig \
    fonts-liberation \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installation de Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Configuration de wkhtmltopdf avec xvfb (wrapper)
RUN echo '#!/bin/bash\nxvfb-run -a --server-args="-screen 0, 1024x768x24" /usr/bin/wkhtmltopdf "$@"' > /usr/local/bin/wkhtmltopdf-xvfb \
    && chmod +x /usr/local/bin/wkhtmltopdf-xvfb

# Création de l'utilisateur système
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers de configuration
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Changement d'utilisateur
USER $user

# Exposition du port
EXPOSE 9000

CMD ["php-fpm"]
