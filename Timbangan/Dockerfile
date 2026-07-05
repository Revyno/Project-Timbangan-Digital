# =============================================================================
# Timbangan Digital — Production Image (multi-stage)
#
#   Stage 1 (assets) : build asset frontend (Vite / Vue) dengan Node
#   Stage 2 (app)    : runtime PHP-FPM + kode Laravel + vendor + asset
#   Stage 3 (nginx)  : web server yang menyajikan /public dan mem-proxy PHP
#
# Dibangun via docker-compose (lihat docker-compose.yml) dengan `target:`.
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1 — Build asset frontend
# -----------------------------------------------------------------------------
FROM node:20-alpine AS assets

WORKDIR /app

# VITE_* di-bake ke dalam bundle JS saat build. Nilainya berasal dari
# build args yang dikirim docker-compose (dari file .env). Kalau domain /
# skema WebSocket berubah, WAJIB `docker compose build` ulang.
ARG VITE_APP_NAME="Timbangan-Digital"
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https

ENV VITE_APP_NAME=$VITE_APP_NAME \
    VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

# Install dependency dulu (layer cache) lalu build
COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


# -----------------------------------------------------------------------------
# Stage 2 — Runtime aplikasi (PHP-FPM)
# -----------------------------------------------------------------------------
FROM php:8.3-fpm AS app

# Ekstensi PHP yang dibutuhkan paket-paket aplikasi:
#   pdo_mysql            -> MySQL
#   bcmath, gmp          -> barcode, web-push
#   gd, exif             -> QR code / barcode / gambar
#   zip                  -> maatwebsite/excel, zipstream
#   intl, mbstring       -> lokalisasi & string
#   pcntl               -> queue worker & reverb (signal handling)
#   opcache              -> performa produksi
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions \
        pdo_mysql \
        mbstring \
        bcmath \
        gmp \
        gd \
        exif \
        zip \
        intl \
        pcntl \
        opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Alat bantu minimal untuk entrypoint (healthcheck DB) & git untuk composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Konfigurasi PHP produksi
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www

# Install dependency PHP produksi (tanpa dev, autoloader dioptimasi).
# composer.json/lock dulu supaya layer vendor ter-cache.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# Salin seluruh kode aplikasi
COPY . .

# Salin hasil build asset dari stage 1
COPY --from=assets /app/public/build ./public/build

# Selesaikan autoloader + package discovery, siapkan symlink storage
RUN composer dump-autoload --optimize --no-dev \
    && php artisan storage:link || true \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

# Entrypoint: tunggu DB, migrate, cache config, lalu jalankan CMD
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]


# -----------------------------------------------------------------------------
# Stage 3 — Test runner (PHPUnit)
#
# Menyertakan dev-dependencies (phpunit) + driver sqlite untuk menjalankan
# test suite (memakai DB sqlite :memory:, lihat phpunit.xml). Tidak butuh
# service MySQL/Reverb. Jalankan via: docker compose --profile test run --rm test
# -----------------------------------------------------------------------------
FROM app AS test

# Driver sqlite untuk test in-memory
RUN install-php-extensions pdo_sqlite sqlite3

# Install juga dev-dependencies (phpunit, dll.)
RUN composer install --no-interaction --prefer-dist

ENV APP_ENV=testing

# Test tidak butuh tunggu DB → lewati entrypoint produksi
ENTRYPOINT []
CMD ["php", "artisan", "test"]


# -----------------------------------------------------------------------------
# Stage 4 — Nginx (menyajikan /public, proxy PHP-FPM & WebSocket Reverb)
# -----------------------------------------------------------------------------
FROM nginx:1.27-alpine AS nginx

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Nginx butuh berkas /public (index.php + asset hasil build) untuk disajikan
COPY --from=app /var/www/public /var/www/public

EXPOSE 80
