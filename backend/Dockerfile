# Один образ — четыре роли (CONTAINER_ROLE: api|queue|scheduler|websocket), см.
# docker/entrypoint.sh и docs/plan/12-infrastructure.md.

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev

FROM php:8.5-fpm-alpine AS final

# install-php-extensions (mlocati/docker-php-extension-installer) вместо ручного
# docker-php-ext-install — сам управляет build-зависимостями и надёжнее на Alpine для
# нескольких расширений подряд (голый docker-php-ext-install intl+opcache в одной сборке
# ловил "cp: can't stat 'modules/*'" — известная хрупкость связки).
RUN apk add --no-cache nginx supervisor procps curl \
    && curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /usr/local/bin/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions pdo_pgsql bcmath intl opcache pcntl

WORKDIR /var/www/html

COPY --from=vendor /app ./
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/healthcheck.sh /healthcheck.sh

RUN chmod +x /entrypoint.sh /healthcheck.sh \
    && chown -R www-data:www-data storage bootstrap/cache

ENV CONTAINER_ROLE=api
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD /healthcheck.sh || exit 1

ENTRYPOINT ["/entrypoint.sh"]
