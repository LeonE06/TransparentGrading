# ---------- 🧱 BASE IMAGE ---------- #
FROM php:8.3-fpm AS base

# Systempakete & PHP-Erweiterungen
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libpng-dev libicu-dev curl \
 && docker-php-ext-configure intl \
 && docker-php-ext-install intl zip pdo pdo_mysql opcache \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer (vom offiziellen Image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html


# ---------- 🧑‍💻 DEV ---------- #
FROM base AS dev
ENV APP_ENV=dev

# Symfony CLI installieren
RUN curl -sS https://get.symfony.com/cli/installer | bash \
 && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Zuerst gesamten Code kopieren
COPY . .

# Abhängigkeiten installieren
RUN composer install --no-interaction

# Schreibrechte für Cache & Logs
RUN mkdir -p var/cache var/log && chmod -R 777 var

EXPOSE 8000
CMD ["symfony", "serve", "--no-tls", "--allow-all-ip", "--port=8000"]


# ---------- 🚀 PROD ---------- #
FROM base AS prod
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Projektdateien kopieren
COPY . .

# Symfony Abhängigkeiten installieren (ohne dev)
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction

# Cache & Rechte fixen
RUN php bin/console cache:clear --env=prod \
 && mkdir -p var/cache var/log \
 && chown -R www-data:www-data /var/www/html \
 && chmod -R 755 var

EXPOSE 9000
# Doctrine Schema automatisch beim Start aktualisieren und Server starten
CMD sh -c "php bin/console doctrine:schema:update --force --no-interaction && php -S 0.0.0.0:${PORT:-8000} -t public"


