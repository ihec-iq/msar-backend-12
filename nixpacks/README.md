# Nixpacks Configuration for Laravel 12+ Application

## Overview

This directory contains an improved and production-ready Nixpacks configuration for deploying Laravel 12+ applications using Nginx, PHP-FPM, and Supervisor on containerized platforms.

## Directory Structure

```
_nixpacks_improved/
├── nixpacks.toml          # Main Nixpacks configuration file
├── Dockerfile             # Optional: Traditional Docker build approach
├── config/
│   ├── nginx.conf         # Nginx web server configuration
│   ├── php-fpm.conf       # PHP-FPM runtime configuration
│   ├── supervisord.conf   # Supervisor process manager configuration
│   └── workers/
│       ├── nginx.conf
│       ├── php-fpm.conf
│       └── laravel-queue-worker.conf
├── scripts/
│   └── start.sh           # Container initialization script
└── README.md              # This file
```

## Key Improvements from Previous Version

### 1. Fixed Build Phase Issues
**Previous Problem:**
```toml
"cp WORKDIR /var/www /_nixpacks/worker-*.conf /etc/supervisor/conf.d/"
```
This command was syntactically incorrect and caused deployment failures.

**Solution:**
- Removed incorrect `cp` command syntax
- Configuration files are now properly embedded in `nixpacks.toml`
- Start script handles file copying with proper error handling

### 2. Simplified Configuration Management
- Separated concerns: build phase only handles dependencies and optimization
- Configuration files are embedded directly in `nixpacks.toml`
- Start script copies configs to system locations with validation

### 3. Container-Optimized Settings
- All processes run in foreground mode (required for containers)
- Log output redirected to stdout/stderr (Docker-friendly)
- Supervisor configured to run with `nodaemon = true`
- PHP-FPM configured to run with `daemonize = no`

### 4. Security Enhancements
- Nginx security headers (X-Content-Type-Options, X-Frame-Options, etc.)
- Proper file permissions in container
- Dedicated www-data user for web processes
- Hidden file access denial

### 5. Performance Optimizations
- Gzip compression enabled in Nginx
- Static file caching (30 days)
- Connection pooling optimized
- PHP-FPM dynamic process management

### 6. Production-Ready Features
- Health check endpoint
- Proper error logging
- Signal handling for graceful shutdown
- Laravel-specific optimizations (route/config caching)

## Configuration Files

### nixpacks.toml
Main configuration file that defines:
- **Phases.setup**: System packages and dependencies
- **Phases.build**: Build commands (composer install, etc.)
- **Start**: Entry point command
- **StaticAssets**: All configuration files embedded as strings

### nginx.conf
Web server configuration including:
- Request routing for Laravel
- PHP-FPM integration via fastcgi
- Static file serving with caching
- Security headers

### php-fpm.conf
PHP-FPM pool configuration including:
- Dynamic process management
- File upload limits (35MB for Laravel)
- Memory and execution settings
- Logging to stdout

### supervisord.conf
Supervisor main configuration including:
- Foreground operation mode
- Process management
- Log aggregation to stdout

### Worker Configurations
Individual Supervisor worker configurations for:
- **nginx**: Web server process
- **php-fpm**: PHP runtime
- **laravel-queue-worker**: Background job processing (disabled by default)

## Deployment Instructions

### For Nixpacks-Based Deployments (Recommended)

1. Replace your current `nixpacks.toml` with the one from `_nixpacks_improved/`

2. The start script will automatically:
   - Copy all configurations to system locations
   - Run Laravel initialization (caching, migrations)
   - Start Supervisor which manages all processes

3. Environment variables can be set at runtime:
```bash
PORT=8000               # Port to listen on (default: 8000)
NIXPACKS_PHP_ROOT_DIR   # Custom PHP root (optional, defaults to /app)
```

### For Docker-Based Deployments

Use the included Dockerfile:
```bash
docker build -t my-laravel-app .
docker run -p 8000:8000 my-laravel-app
```

## Enabling Queue Workers

By default, Laravel queue workers are disabled (`autostart = false`). To enable:

1. Set environment variable: `SUPERVISOR_ENABLE_QUEUE=true`
2. Or modify the configuration to set `autostart = true`

## Monitoring and Debugging

### Check Process Status
```bash
supervisorctl status
```

### View Logs
All logs are output to stdout/stderr and visible via:
```bash
docker logs <container_id>
```

### Common Issues

**Nginx fails to start:**
- Check if port 8000 is already in use
- Verify nginx.conf syntax: `nginx -t`

**PHP-FPM not responding:**
- Verify PHP-FPM is listening on 127.0.0.1:9000
- Check PHP-FPM configuration syntax

**Queue workers not processing:**
- Ensure `autostart = true` in laravel-queue-worker.conf
- Check Laravel queue driver is properly configured

## Environment Variables

- `PORT`: Port for Nginx to listen on (default: 8000)
- `APP_ENV`: Laravel environment (default: production)
- `APP_DEBUG`: Laravel debug mode (default: false)
- `LOG_CHANNEL`: Laravel log channel (default: stack)

## Performance Tuning

### PHP-FPM Settings
Adjust in `php-fpm.conf` under `[www]`:
- `pm.max_children`: Maximum processes (default: 50)
- `pm.start_servers`: Starting processes (default: 10)
- `pm.min_spare_servers`: Minimum spare (default: 5)
- `pm.max_spare_servers`: Maximum spare (default: 20)

### Nginx Settings
Adjust in `nginx.conf`:
- `worker_processes`: CPU cores (auto-detect by default)
- `worker_connections`: Max connections per worker (default: 2048)

### PHP Memory
Adjust in `php-fpm.conf`:
- `php_admin_value[memory_limit]`: Memory limit (default: 256M)

## Troubleshooting

### Clear Laravel Caches
In the container:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Restart Supervisor
```bash
supervisorctl restart all
```

### Check Configuration Syntax
```bash
supervisord -c /etc/supervisord.conf test
php-fpm -y /etc/php-fpm/php-fpm.conf --test
```

## Support

For issues or questions:
1. Check logs: `docker logs <container_id>`
2. Verify configuration files are in place: `ls -la /etc/supervisor/conf.d/`
3. Test individual services manually

## License

Same as the parent Laravel application.
