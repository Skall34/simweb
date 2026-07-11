FROM php:8.4-fpm

# Dépendances système (AVANT docker-php-ext-install)
# libonig-dev   → mbstring
# libcurl4-openssl-dev + libssl-dev → curl
RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP requises par SimWeb
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    curl

# Répertoire de travail
WORKDIR /var/www/html

# Copier le code source
COPY . /var/www/html/

# Créer les dossiers manquants (ignorés par git) et fixer les permissions
RUN mkdir -p /var/www/html/scripts/logs \
    && mkdir -p /var/www/html/assets/images \
    && chown -R www-data:www-data /var/www/html/scripts/logs \
    && chown -R www-data:www-data /var/www/html/assets

EXPOSE 9000
