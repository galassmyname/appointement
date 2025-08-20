# =========================
# IMAGE DE BASE
# =========================
FROM php:8.2-fpm

# =========================
# INSTALLER LES DEPENDANCES SYSTEME
# =========================
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    git \
    curl \
    nodejs \
    npm \
    && docker-php-ext-install intl bcmath zip pdo_mysql

# =========================
# INSTALLER COMPOSER
# =========================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================
# DEFINIR LE REPERTOIRE DE TRAVAIL
# =========================
WORKDIR /var/www/html

# =========================
# COPIER LE PROJET
# =========================
COPY . .

# =========================
# INSTALLER LES DEPENDANCES PHP
# =========================
RUN composer install --optimize-autoloader --no-dev --no-interaction

# =========================
# INSTALLER LES DEPENDANCES FRONT-END
# =========================
RUN npm install
RUN npm run build

# =========================
# CONFIGURER LES PERMISSIONS
# =========================
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# =========================
# EXPOSER LE PORT
# =========================
EXPOSE 8000

# =========================
# COMMAND POUR LANCER L'APPLICATION
# =========================
CMD php artisan serve --host=0.0.0.0 --port=8000
