#!/usr/bin/env bash
set -e

# تهيئة Laravel (اختياري)
if [ -f /var/www/artisan ]; then
  php /var/www/artisan config:cache || true
  php /var/www/artisan route:cache || true
  php /var/www/artisan view:cache || true
fi

# تشغيل Supervisord (هو يدير nginx و php-fpm والعمال)
exec /usr/bin/supervisord -c /etc/supervisord.conf
