FROM php:8.2-fpm

# حزم لازمة + nginx + supervisor
RUN apt-get update && apt-get install -y \
    git unzip zip \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev \
    default-mysql-client \
    nginx supervisor \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd zip pdo pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# مجلد العمل
WORKDIR /var/www

# نسخ المشروع
COPY . .

# تثبيت باكجات PHP (اختياري)
RUN composer install --no-dev --optimize-autoloader || true

# صلاحيات Laravel (إن وُجد)
RUN chown -R www-data:www-data storage bootstrap/cache || true

# 🔹 انسخ مجلد _nixpacks كاملاً إلى مسار ثابت في النظام
COPY _nixpacks /_nixpacks

# 🔹 ضبّط ملفات Supervisor وامنح start.sh صلاحية التنفيذ
RUN mkdir -p /etc/supervisor/conf.d \
    && cp /_nixpacks/worker-*.conf /etc/supervisor/conf.d/ \
    && cp /_nixpacks/supervisord.conf /etc/supervisord.conf \
    && chmod +x /_nixpacks/start.sh

# 🔹 nginx و php-fpm config (لو تستخدمهم من مجلدك)
RUN mkdir -p /var/log/nginx /var/cache/nginx
COPY _nixpacks/nginx.template.conf /etc/nginx/conf.d/default.conf
COPY _nixpacks/php-fpm.conf /_nixpacks/php-fpm.conf

EXPOSE 80

CMD ["/_nixpacks/start.sh"]
