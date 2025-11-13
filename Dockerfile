# Dockerfile for Laravel 12+ application with Nginx, PHP-FPM, and Supervisor
# Multi-stage build for optimized production image

# Build stage
FROM php:8.2-fpm-alpine AS builder

RUN apk add --no-cache \
    bash \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Production stage
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    nginx \
    supervisor \
    curl \
    libpng \
    libjpeg-turbo \
    freetype \
    icu \
    oniguruma \
    mysql-client \
    gettext

RUN addgroup -g 82 -S www-data && \
    adduser -u 82 -D -S -G www-data www-data

WORKDIR /app

COPY --from=builder /app /app
COPY --chown=www-data:www-data . /app

RUN mkdir -p /var/log/nginx \
    && mkdir -p /etc/supervisor/conf.d \
    && mkdir -p /tmp \
    && chown -R www-data:www-data /app/storage \
    && chown -R www-data:www-data /app/bootstrap/cache

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8000}/health || exit 1

CMD ["/bin/bash", "/nixpacks/scripts/start.sh"]
