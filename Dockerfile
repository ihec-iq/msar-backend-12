# ===== Base Image =====
FROM php:8.2-fpm

# ===== OS deps (nginx + supervisor + tools) =====
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install zip gd

# composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ===== App files =====
WORKDIR /var/www
COPY . /var/www

# انسخ مجلد إعداداتنا إلى جذر النظام حتى تبقى المسارات المتوقعة كما هي
# (وبالتالي ما نغيّر أي شيء داخل ملفات الإعداد)
RUN cp -r /var/www/_nixpacks /_nixpacks

# إعدادات supervisor
RUN mkdir -p /etc/supervisor/conf.d \
 && cp /_nixpacks/worker-*.conf /etc/supervisor/conf.d/ \
 && cp /_nixpacks/supervisord.conf /etc/supervisord.conf \
 && chmod +x /_nixpacks/start.sh

# Laravel deps (اختياري: إذا تحتاج dev packages شيل --no-dev)
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# ===== Entrypoint =====
CMD ["/_nixpacks/start.sh"]
