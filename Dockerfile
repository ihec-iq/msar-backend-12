FROM php:8.2-fpm

# تثبيت الأدوات المطلوبة
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    default-mysql-client

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تحديد مجلد العمل
WORKDIR /var/www

# نسخ كل ملفات المشروع
COPY . .

# تثبيت الحزم
RUN composer install --no-dev --optimize-autoloader

# إعداد صلاحيات Laravel (اختياري)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
