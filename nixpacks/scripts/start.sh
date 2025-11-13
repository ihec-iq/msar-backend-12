#!/usr/bin/env bash
set -e

# Container startup script for Laravel application
# Manages initialization and supervisor startup

echo "Starting Laravel application container..."

# Create required directories
mkdir -p /tmp
mkdir -p /var/log
mkdir -p /etc/nginx
mkdir -p /etc/php-fpm
mkdir -p /etc/supervisor/conf.d

# Copy configuration files from _nixpacks to system locations
echo "Setting up configuration files..."

# Copy Nginx configuration
cp /nixpacks/config/nginx.conf /etc/nginx/nginx.conf
chmod 644 /etc/nginx/nginx.conf

# Copy PHP-FPM configuration
cp /nixpacks/config/php-fpm.conf /etc/php-fpm/php-fpm.conf
chmod 644 /etc/php-fpm/php-fpm.conf

# Copy Supervisor configuration
cp /nixpacks/config/supervisord.conf /etc/supervisord.conf
chmod 644 /etc/supervisord.conf

# Copy worker configurations
cp /nixpacks/config/workers/*.conf /etc/supervisor/conf.d/
chmod 644 /etc/supervisor/conf.d/*.conf

# Laravel initialization (if artisan exists)
if [ -f /app/artisan ]; then
    echo "Running Laravel initialization..."
    php /app/artisan config:cache || true
    php /app/artisan route:cache || true
    php /app/artisan view:cache || true
    php /app/artisan migrate --force || true
fi

# Start supervisor in foreground mode
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
