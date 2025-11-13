# Dockerfile for Laravel 12+ application
# Multi-stage build for optimized production image
# Built with support for Nginx, PHP-FPM, and Supervisor

# Build stage - prepare application code and dependencies
FROM php:8.2-fpm-alpine AS builder

# Install required system packages
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

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
WORKDIR /app
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Production stage - minimal runtime image
FROM php:8.2-fpm-alpine

# Install required system packages
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
    mysql-client

# Create application user
RUN addgroup -g 82 -S www-data && \
    adduser -u 82 -D -S -G www-data www-data

# Set working directory
WORKDIR /app

# Copy application from builder stage
COPY --from=builder /app /app
COPY --chown=www-data:www-data . /app

# Copy nixpacks configuration (this will be provided at build time)
COPY _nixpacks /_nixpacks

# Create required directories
RUN mkdir -p /var/log/nginx \
    && mkdir -p /etc/supervisor/conf.d \
    && mkdir -p /tmp \
    && chown -R www-data:www-data /app/storage \
    && chown -R www-data:www-data /app/bootstrap/cache

# Ensure start script is executable
RUN chmod +x /_nixpacks/scripts/start.sh

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/health || exit 1

# Set container startup command
CMD ["/bin/bash", "/_nixpacks/scripts/start.sh"]
