FROM php:8.2-fpm

# حزم النظام + nginx + supervisor + ملحقات PHP الشائعة
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor git unzip libzip-dev zip \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev \
    default-mysql-client ca-certificates curl \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install zip gd pdo pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# كود المشروع
WORKDIR /var/www
COPY . .

# تثبيت PHP deps (لو عندك composer.json)
RUN composer install --no-dev --optimize-autoloader || true

# صلاحيات Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

# نسخ ملفات التشغيل (المجلد الجديد _nixpacks)
COPY _nixpacks/ /_nixpacks/

# تفعيل إعدادات nginx و supervisor و صلاحيات السكربت
RUN mkdir -p /etc/supervisor/conf.d /run/php \
 && cp /_nixpacks/nginx.template.conf /etc/nginx/nginx.conf \
 && cp /_nixpacks/worker-*.conf /etc/supervisor/conf.d/ \
 && cp /_nixpacks/supervisord.conf /etc/supervisord.conf \
 && chmod +x /_nixpacks/start.sh

# المنفذ
EXPOSE 80

# التشغيل
CMD ["bash", "/_nixpacks/start.sh"]
