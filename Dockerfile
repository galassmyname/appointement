# Utiliser PHP 8.2 FPM
FROM php:8.2-fpm

# Installer les dépendances système pour les extensions PHP
RUN apt-get update && apt-get install -y \
    libicu-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install intl bcmath zip pdo_mysql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le projet dans le conteneur
WORKDIR /var/www/html
COPY . .

# Installer les dépendances Laravel
RUN composer install --optimize-autoloader --no-interaction

# Exposer le port 8000
EXPOSE 8000

# Lancer Laravel
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000

