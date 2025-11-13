# ===== Base image (PHP-FPM) =====
FROM php:8.2-fpm

# ===== System & PHP extensions =====
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor \
    git unzip ca-certificates \
    libzip-dev zip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev \
    default-mysql-client \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install zip gd \
 && rm -rf /var/lib/apt/lists/*

# ===== Composer =====
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ===== Workdir =====
WORKDIR /app

# ===== App files =====
COPY . /app

# ===== _nixpacks assets (المجلد الذي يحتوي ملفات المشرف والبدء) =====
COPY _nixpacks /_nixpacks

# انسخ إعدادات PHP-FPM (نفس المسار الذي تحتاجه السكربتات/السوبرفايزر)
RUN mkdir -p /assets \
 && cp /_nixpacks/php-fpm.conf /assets/php-fpm.conf

# ===== Supervisor configs =====
RUN mkdir -p /etc/supervisor/conf.d \
 && cp /_nixpacks/worker-*.conf /etc/supervisor/conf.d/ \
 && cp /_nixpacks/supervisord.conf /etc/supervisord.conf \
 && chmod +x /_nixpacks/start.sh

# ===== PHP deps & permissions =====
RUN composer install --no-dev --prefer-dist --optimize-autoloader \
 && chown -R www-data:www-data /app/storage /app/bootstrap/cache

# المنفذ الذي سيخدمه Nginx داخل الحاوية
EXPOSE 80

# ===== Start everything (nginx + php-fpm عبر supervisord) =====
CMD ["/_nixpacks/start.sh"]
