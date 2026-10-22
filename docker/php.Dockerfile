# Image de travail du projet — base PUBLIQUE php:8.4-cli, la version visée sur l'hébergement mutualisé
# (NFR-02, ADR-004). Extensions : pdo_mysql, gd (jpeg, webp, freetype), intl, zip (paquet) ; sodium et mbstring
# sont fournis par l'image officielle. Composer sert uniquement au build (dépendances embarquées).
FROM php:8.4-cli
RUN apt-get update -y \
 && apt-get install -y --no-install-recommends unzip git libicu-dev libjpeg62-turbo-dev libpng-dev \
      libwebp-dev libfreetype6-dev libzip-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl zip \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
