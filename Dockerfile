FROM php:8.2-fpm

# تثبيت الأدوات والمكتبات اللازمة
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip gd

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# نسخ ملفات الـ nixpacks
COPY .nixpacks/ .nixpacks/

# تحديد مجلد العمل
WORKDIR /var/www

# نسخ كل ملفات المشروع
COPY . .

# تثبيت الحزم
RUN composer install --no-dev --optimize-autoloader

# إعداد صلاحيات Laravel (اختياري)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
