FROM php:8.2-fpm

# تثبيت الأدوات المطلوبة
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    mysql-client

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تحديد مجلد العمل
WORKDIR /var/www

# نسخ الملفات إلى الحاوية
COPY . .

# تثبيت الاعتمادات
RUN composer install --no-dev --optimize-autoloader
