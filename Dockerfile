# Production image for Railway. HTTP is served by FrankenPHP (Caddy with
# PHP embedded) as the unprivileged www-data user; the same Dockerfile serves
# the three Railway services (web, worker, scheduler) -- only each one's
# "Custom Start Command" changes, see deploy/railway.md.

FROM node:20-alpine@sha256:fb4cd12c85ee03686f6af5362a0b0d56d50c58a04632e6c0fb8363f609372293 AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
# Sem .env neste estágio (.dockerignore exclui .env* de propósito), então o
# Vite não acha VITE_APP_NAME e o título da aba cai no fallback "Laravel" --
# passar direto como build arg evita depender de um .env que nunca deveria
# estar na imagem.
ARG VITE_APP_NAME="Hackathon IFPR"
ENV VITE_APP_NAME=$VITE_APP_NAME
RUN npm run build

FROM dunglas/frankenphp:1.13.5-php8.4-alpine@sha256:0b9e020d7c3c17157d8fb051f67ce329b816fcbba456a19c45613fe0ef9fcb78 AS app

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    su-exec \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql gd zip bcmath intl exif pcntl
# mbstring and opcache already ship with the FrankenPHP base image.
# enable_cli=1 keeps opcache on for the worker and scheduler (`php artisan`,
# SAPI cli). validate_timestamps=0 because the image is rebuilt on every
# deploy -- checking file mtimes on every request has nothing to catch.
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.max_accelerated_files=20000'; \
    } > /usr/local/etc/php/conf.d/opcache-prod.ini
# Production runtime: no PHP version banner, errors to the log (stderr)
# instead of the response. Upload limits sit above the largest Form Request
# limit (25 MB submission file) so oversized files reach Laravel's
# validation and get a Portuguese message instead of being dropped by PHP.
RUN { \
    echo 'expose_php=Off'; \
    echo 'display_errors=Off'; \
    echo 'display_startup_errors=Off'; \
    echo 'log_errors=On'; \
    echo 'upload_max_filesize=30M'; \
    echo 'post_max_size=32M'; \
    } > /usr/local/etc/php/conf.d/zz-production.ini

COPY --from=composer:2@sha256:ca11f643d7791ebdd90a34fc20caf11380d434007ae539a9ea75cd9212e13dca /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# INSTALL_DEV=1 só no ambiente de homologação: o DemoSeeder usa factories
# (Faker é dependência de dev). Em produção fica 0 e a imagem sai sem dev.
ARG INSTALL_DEV=0
COPY composer.json composer.lock ./
# Retry por causa de 429 esporádico do GitHub em download anônimo de dist
# (codeload.github.com) -- não é falha do lockfile, só limite de taxa por IP.
RUN FLAGS="$([ "$INSTALL_DEV" = 1 ] || echo --no-dev)"; \
    composer install $FLAGS --no-interaction --no-scripts --no-autoloader --optimize-autoloader \
    || (sleep 20 && composer install $FLAGS --no-interaction --no-scripts --no-autoloader --optimize-autoloader)

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize $([ "$INSTALL_DEV" = 1 ] || echo --no-dev)

# Non-root runtime: www-data owns only what the app writes (storage,
# bootstrap/cache) and Caddy's data/config dirs. The base image hands the app
# root itself to www-data (1777), which would let it rename vendor/ or app/,
# so it goes back to root. The public/storage link is made here with ln
# (what storage:link does) so public/ stays root-owned. The binding
# capability is dropped: $PORT is above 1024, and a file capability breaks
# exec under no-new-privileges. Composer's dist archives come out with
# 0777 directories (unzip keeps the archive's modes), so every world-writable
# bit under the app root is cleared: www-data writes only where it owns.
RUN chown root:root /var/www/html && chmod 755 /var/www/html \
    && find /var/www/html -xdev -perm -0002 ! -type l -exec chmod o-w {} + \
    && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy \
    && ln -sfn /var/www/html/storage/app/public public/storage \
    && setcap -r /usr/local/bin/frankenphp

COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint

# The entrypoint starts as root only to hand the root-owned Railway volume
# (storage/app/private) to www-data, then drops to www-data with su-exec.
# migrate --force runs on every web boot (idempotent; it only applies what
# is missing) -- not at build time, when APP_KEY and DB_* do not exist yet.
# Worker and scheduler replace this with their "Custom Start Command"; on
# Railway that also replaces the ENTRYPOINT, so prefix it with
# app-entrypoint to run them as www-data too -- see deploy/railway.md.
EXPOSE 8080
ENTRYPOINT ["app-entrypoint"]
CMD ["sh", "-c", "php artisan migrate --force; exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile"]
