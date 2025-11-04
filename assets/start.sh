 #!/bin/bash

# نسخ ملف NGINX الجاهز
cp /assets/nginx.template.conf /etc/nginx.conf

# تشغيل PHP-FPM بالخلفية
php-fpm -y /assets/php-fpm.conf -D

# إبقاء NGINX في الواجهة
nginx -g "daemon off;"

