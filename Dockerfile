# Utiliser PHP 8.2 FPM pour production
FROM php:8.2-fpm

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    libicu-dev \
    zip \
    unzip \
    libzip-dev \
    git \
    curl \
    && docker-php-ext-install intl bcmath zip pdo_mysql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le projet
WORKDIR /var/www/html
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Donner les droits à storage et bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Exposer le port attendu par Railway
EXPOSE 8080

# Démarrer le serveur PHP intégré
CMD php -S 0.0.0.0:8080 -t public
