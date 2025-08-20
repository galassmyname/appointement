# Utiliser PHP FPM 8.2
FROM php:8.2-fpm

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    git \
    curl \
    && docker-php-ext-install intl bcmath zip pdo_mysql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le projet
COPY . .

# Définir les permissions correctes pour Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Installer les dépendances Laravel
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Exposer le port
EXPOSE 8000
RUN mkdir -p /var/www/html/public/build && echo '{}' > /var/www/html/public/build/manifest.json
RUN php artisan filament:assets || true
RUN php artisan config:cache || true
RUN php artisan route:cache || true
RUN php artisan view:cache || true

# Lancer Laravel en utilisant les variables Railway
CMD php artisan serve --host=0.0.0.0 --port=8000
