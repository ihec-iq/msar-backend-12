# -------- PHP base --------
FROM php:8.2-fpm

# نظام وحزم لازمة + nginx + supervisor
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

# نسخ المشروع كله
COPY . .

# تثبيت باكجات PHP (لو عندك composer.json)
RUN composer install --no-dev --optimize-autoloader || true

# صلاحيات Laravel (لو موجود)
RUN chown -R www-data:www-data storage bootstrap/cache || true

# ------- نسخ وضبط ملفات _nixpacks -------
# ننشئ مجلد سوبرفايرزر وننسخ ملفات العمال
RUN mkdir -p /etc/supervisor/conf.d \
    && cp _nixpacks/worker-*.conf /etc/supervisor/conf.d/ \
    && cp _nixpacks/supervisord.conf /etc/supervisord.conf \
    # ننسخ ملفات _nixpacks أيضاً إلى جذر النظام لأن الكونفِغ يشير إلى /_nixpacks/...
    && mkdir -p /_nixpacks \
    && cp -r _nixpacks/* /_nixpacks/ \
    && chmod +x /_nixpacks/start.sh

# ملفات nginx و php-fpm (لو تحب تعتمدها من المجلد)
# ملفك: _nixpacks/nginx.template.conf -> نجعله default.conf
RUN mkdir -p /var/log/nginx /var/cache/nginx
COPY _nixpacks/nginx.template.conf /etc/nginx/conf.d/default.conf

# ملف php-fpm الخاص بك (يجب أن يكون متوافق مع العامل في supervisor)
# إن كنت تستخدمه كملف مسبح/كونفج جاهز:
COPY _nixpacks/php-fpm.conf /_nixpacks/php-fpm.conf

# المنفذ الافتراضي للويب
EXPOSE 80

# الأمر الرئيسي: يشغّل supervisor والذي يدير nginx و php-fpm
CMD ["/_nixpacks/start.sh"]
