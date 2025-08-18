# Utiliser PHP CLI pour Laravel
FROM php:8.2-cli

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

# Exposer le port fourni par Railway
EXPOSE $PORT

# Lancer Laravel
CMD php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=$PORT
