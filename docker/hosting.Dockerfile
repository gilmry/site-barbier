# Hébergement mutualisé SIMULÉ (gate `hebergement`) — base PUBLIQUE php:8.4-apache, comme l'offre OVH
# Perso : Apache + .htaccess, racine web imposée (www/), aucun accès en ligne de commande pendant le test.
# Le paquet est déposé tel quel dans /srv/site (équivalent de la racine SFTP).
FROM php:8.4-apache
RUN apt-get update -y \
 && apt-get install -y --no-install-recommends libicu-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod rewrite headers \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && sed -i 's/^expose_php = On/expose_php = Off/' "$PHP_INI_DIR/php.ini"
ENV SITE_DOCROOT=/srv/site/www
RUN printf '%s\n' \
  '<VirtualHost *:80>' \
  '  DocumentRoot ${SITE_DOCROOT}' \
  '  <Directory /srv/site>' \
  '    AllowOverride All' \
  '    Require all granted' \
  '  </Directory>' \
  '</VirtualHost>' > /etc/apache2/sites-available/000-default.conf
