# الصورة الأساسية
FROM php:8.2-fpm

# تثبيت الأدوات والمكتبات
RUN apt-get update && apt-get install -y \
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
    nodejs \
    python3 \
    python3-pip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip gd

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# نسخ ملفات المشروع بما فيها _nixpacks
COPY . /var/www

# تحديد مجلد العمل
WORKDIR /var/www

# إعداد Supervisor
RUN mkdir -p /etc/supervisor/conf.d/ \
 && cp _nixpacks/worker-*.conf /etc/supervisor/conf.d/ \
 && cp _nixpacks/supervisord.conf /etc/supervisord.conf \
 && chmod +x _nixpacks/start.sh

# تثبيت الحزم
RUN composer install --no-dev --optimize-autoloader

# إعداد صلاحيات Laravel (اختياري)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# أمر التشغيل
CMD ["/_nixpacks/start.sh"]
