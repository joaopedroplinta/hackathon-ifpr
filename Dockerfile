# Imagem de demo para Railway — não é o deploy final do evento (esse ainda
# depende da decisão de hospedagem no Brasil, PLANO.md §10). Processo único
# via `php artisan serve`: tráfego de uma demo pra professora não justifica
# nginx+php-fpm; o mesmo Dockerfile serve os três serviços Railway (web,
# worker, scheduler) — só muda o "Custom Start Command" de cada um no
# painel, ver deploy/railway.md.

FROM node:20-alpine AS assets

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

FROM php:8.5-cli-alpine AS app

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql gd zip bcmath intl mbstring exif pcntl
# opcache fica de fora de propósito: quebra o build nessa imagem
# php:8.5-cli-alpine (bug da extensão nessa versão recente do PHP, não do
# Dockerfile -- `docker-php-ext-install opcache` falha isolado, sem gd/zip/
# etc envolvidos). Ganho de performance, não requisito -- não vale travar a
# demo por isso.

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
# Retry por causa de 429 esporádico do GitHub em download anônimo de dist
# (codeload.github.com) -- não é falha do lockfile, só limite de taxa por IP.
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --optimize-autoloader \
    || (sleep 20 && composer install --no-dev --no-interaction --no-scripts --no-autoloader --optimize-autoloader)

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev

# storage:link e migrate rodam no start, não no build -- nesse ponto o
# container ainda não tem as env vars de runtime (APP_KEY, DB_*) que o
# Railway só injeta ao subir o serviço, e bootar o framework sem elas pode
# falhar. migrate --force roda em todo boot do serviço web (idempotente, só
# aplica o que falta) -- diferente da Render, o Railway tem `railway run`
# pra comando avulso, mas manter no CMD evita um passo manual a cada deploy.
# Só o serviço web roda essa linha: worker e scheduler sobrescrevem o CMD
# com "Custom Start Command" sem storage:link/migrate -- ver deploy/railway.md.
RUN chmod -R a+w storage bootstrap/cache

EXPOSE 8080
CMD ["sh", "-c", "php artisan storage:link --force; php artisan migrate --force; php artisan serve --host 0.0.0.0 --port ${PORT:-8080}"]
