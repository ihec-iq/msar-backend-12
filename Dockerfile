FROM php:8.2-fpm

# تثبيت أدوات النظام وامتدادات PHP المطلوبة
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    default-mysql-client \
    && docker-php-ext-install zip

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
