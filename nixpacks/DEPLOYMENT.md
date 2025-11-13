# Deployment Instructions

## Quick Start (5 minutes)

### Step 1: Copy Files to Your Project
```bash
# Copy entire nixpacks folder to your repository root
cp -r nixpacks /path/to/your/laravel/project/

# Copy nixpacks.toml to repository root (required by Nixpacks)
cp nixpacks/nixpacks.toml /path/to/your/laravel/project/
```

### Step 2: Update Configuration
```bash
# Navigate to your project
cd /path/to/your/laravel/project

# Create .env file from template
cp nixpacks/.env.example .env

# Edit with your settings
nano .env
# OR
vim .env
```

**Required changes in .env:**
- `DB_HOST` - Your database host
- `DB_USERNAME` - Database user
- `DB_PASSWORD` - Database password
- `DB_DATABASE` - Database name
- `APP_KEY` - Laravel application key (generate if needed)

### Step 3: Commit and Push
```bash
git add .
git commit -m "feat: add nixpacks configuration for production deployment"
git push origin deploy
```

### Step 4: Monitor Deployment
- Platform detects `nixpacks.toml` automatically
- Deployment begins
- Check logs for progress
- Application available in 3-5 minutes

---

## Detailed Deployment Guide

### For Fly.io
```bash
# Set environment variables
flyctl secrets set APP_KEY="base64:xxx"
flyctl secrets set DB_HOST="mysql.internal"
flyctl secrets set DB_USERNAME="user"
flyctl secrets set DB_PASSWORD="password"
flyctl secrets set DB_DATABASE="laravel"

# Deploy
git push origin deploy
# Or manually trigger
flyctl deploy
```

### For Railway
```bash
# Connect your GitHub repository
# Railway auto-detects nixpacks.toml

# Add environment variables in Railway dashboard
# Then push your code

git push origin deploy
```

### For Render
```bash
# Connect GitHub repository
# Select Docker as build type
# Railway auto-detects Dockerfile or nixpacks.toml

# Configure environment variables
# Deploy
```

### For Traditional Docker
```bash
# Build image
docker build -t my-laravel-app:latest .

# Run container
docker run -d \
  -p 8000:8000 \
  -e APP_KEY="base64:xxx" \
  -e DB_HOST="mysql" \
  -e DB_USERNAME="user" \
  -e DB_PASSWORD="password" \
  -e DB_DATABASE="laravel" \
  my-laravel-app:latest
```

---

## Verification After Deployment

### 1. Check Container Startup
```bash
# View logs (last 50 lines)
docker logs -n 50 <container_id>

# Expected output:
# Starting Laravel application container...
# Setting up configuration files...
# Running Laravel initialization...
# Starting Supervisor...
```

### 2. Verify Supervisor Processes
```bash
# Check process status
docker exec <container_id> supervisorctl status

# Expected:
# nginx                            RUNNING
# php-fpm                          RUNNING
# laravel-queue                    STOPPED (if not enabled)
```

### 3. Test Application
```bash
# Health check
curl http://your-domain:8000/health
# Should return 200 OK

# Web request
curl http://your-domain:8000/
# Should return your Laravel app
```

### 4. Check Configuration Files
```bash
# Verify Nginx config
docker exec <container_id> nginx -t

# Verify PHP-FPM config
docker exec <container_id> php-fpm -t

# Verify Supervisor config
docker exec <container_id> supervisorctl status
```

---

## Environment Variables Reference

### Essential Variables
```bash
# Laravel Application
APP_NAME="My Application"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxx  # Generate with: php artisan key:generate
APP_URL=https://your-domain.com

# Port
PORT=8000  # Nginx listen port
```

### Database Configuration
```bash
DB_CONNECTION=mysql
DB_HOST=mysql.internal  # Your database host
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=secure_password
```

### Cache & Session
```bash
CACHE_DRIVER=redis
CACHE_HOST=redis.internal
CACHE_PORT=6379

SESSION_DRIVER=redis
```

### Queue (if enabled)
```bash
QUEUE_CONNECTION=redis
REDIS_HOST=redis.internal
REDIS_PORT=6379
```

### Mail
```bash
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Logging
```bash
LOG_CHANNEL=stack
LOG_LEVEL=info
```

---

## Post-Deployment Tasks

### 1. Database Setup
```bash
# Connect to container
docker exec -it <container_id> bash

# Run migrations
php artisan migrate

# Seed database (if needed)
php artisan db:seed
```

### 2. Enable Queue Workers (optional)
```bash
# Edit config/workers/laravel-queue-worker.conf
# Change: autostart = false
# To:     autostart = true

# Update nixpacks.toml with new config
# Redeploy
```

### 3. Setup SSL/TLS
```bash
# Most platforms handle this automatically
# For manual setup, use Let's Encrypt

# Update APP_URL in .env to https://
APP_URL=https://your-domain.com
```

### 4. Configure Backups
```bash
# Setup database backups
# Setup storage backups
# Configure log retention
```

---

## Troubleshooting

### Deployment Fails at Build
**Issue**: Build phase returns error
```bash
# Check composer.json validity
composer validate

# Verify PHP extensions needed
php -m

# Check file permissions
find . -type f ! -perm 644 -ls
```

### Container Won't Start
**Issue**: Container exits immediately
```bash
# Check logs
docker logs <container_id>

# Verify start.sh is executable
ls -la nixpacks/scripts/start.sh

# Check configuration files
docker exec <container_id> cat /nixpacks/nginx.conf
```

### Application Returns 502 Error
**Issue**: Bad Gateway
```bash
# Check PHP-FPM status
docker exec <container_id> supervisorctl status php-fpm

# Check PHP-FPM logs
docker logs <container_id> | grep "php-fpm"

# Check Nginx logs
docker logs <container_id> | grep "nginx"
```

### High Memory Usage
**Issue**: Container using excessive memory
```bash
# Reduce PHP-FPM workers
# Edit: nixpacks/config/php-fpm.conf
pm.max_children = 25  # Reduce from 50

# Update nixpacks.toml
# Redeploy
```

### Database Connection Failed
**Issue**: Cannot connect to database
```bash
# Verify connection credentials in .env
# Check database host is accessible
# Ensure database is running

# Test connection
php artisan tinker
# In tinker: DB::connection()->getPdo()
```

---

## Rollback Procedure

If deployment has issues:

### Option 1: Git Rollback
```bash
# Find previous commit
git log --oneline | head -5

# Revert to previous version
git revert <commit_hash>
git push origin deploy

# Platform will redeploy previous version
```

### Option 2: Manual Rollback
```bash
# Stop current container
docker stop <container_id>

# Restore backup
# Deploy previous version

# Verify deployment
docker logs <new_container_id>
```

---

## Performance Tuning

### If Application Is Slow

**Increase PHP-FPM Workers:**
```ini
# In nixpacks/config/php-fpm.conf
pm.max_children = 100      # Increase from 50
pm.start_servers = 20      # Increase from 10
pm.max_spare_servers = 40  # Increase from 20
```

**Increase Nginx Buffers:**
```nginx
# In nixpacks/config/nginx.conf
fastcgi_buffer_size 32k;
fastcgi_buffers 8 32k;
```

**Optimize Laravel:**
```bash
# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear unnecessary logs
php artisan logs:prune
```

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] `APP_KEY` is strong and unique
- [ ] Database password is strong
- [ ] HTTPS/SSL configured
- [ ] Security headers in place
- [ ] CORS properly configured
- [ ] Rate limiting enabled
- [ ] API authentication configured
- [ ] Logs monitored regularly

---

## Support

For issues or questions:
1. Check `README.md` in nixpacks folder
2. Review `IMPROVEMENTS.md` for details
3. Check container logs
4. Verify configuration syntax
5. Test individual components

---

## Additional Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Nixpacks Documentation**: https://nixpacks.com/docs
- **Nginx Documentation**: https://nginx.org/en/docs/
- **PHP-FPM Documentation**: https://www.php.net/manual/en/install.fpm.php
- **Supervisor Documentation**: http://supervisord.org/

---

**Version**: 1.0
**Date**: November 13, 2025
**Status**: ✅ Production Ready
