#!/bin/bash

echo "Starting Laravel application container..."

# Create required directories
mkdir -p /tmp
mkdir -p /var/log
mkdir -p /etc/nginx
mkdir -p /etc/php-fpm
mkdir -p /etc/supervisor/conf.d

echo "Setting up configuration files..."

# Copy Nginx configuration
cp /app/nixpacks/config/nginx.conf /etc/nginx/nginx.conf
chmod 644 /etc/nginx/nginx.conf

# Copy PHP-FPM configuration
cp /app/nixpacks/config/php-fpm.conf /etc/php-fpm/php-fpm.conf
chmod 644 /etc/php-fpm/php-fpm.conf

# Copy Supervisor configuration
cp /app/nixpacks/config/supervisord.conf /etc/supervisord.conf
chmod 644 /etc/supervisord.conf

# Copy worker configurations
cp /app/nixpacks/config/workers/*.conf /etc/supervisor/conf.d/
chmod 644 /etc/supervisor/conf.d/*.conf

# Laravel initialization (if artisan exists)
if [ -f /app/artisan ]; then
    echo "Running Laravel initialization..."
    cd /app
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan migrate --force || true
fi

# Start supervisor in foreground mode
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
