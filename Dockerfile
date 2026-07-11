FROM php:8.4-fpm

# Extensions PHP requises par SimWeb
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring

# curl et openssl sont requis (installés via apt + extension)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libssl-dev \
    unzip \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

# Répertoire de travail
WORKDIR /var/www/html

# Copier le code source
COPY . /var/www/html/

# Permissions pour les dossiers d'écriture
RUN chown -R www-data:www-data /var/www/html/scripts/logs \
    && chown -R www-data:www-data /var/www/html/assets

EXPOSE 9000
