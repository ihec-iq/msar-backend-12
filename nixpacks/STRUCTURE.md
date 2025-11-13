# Nixpacks Configuration Structure Guide

## Directory Structure

```
nixpacks/                          # Main nixpacks configuration folder
├── nixpacks.toml                  # Main configuration file (in root + here for reference)
├── Dockerfile                     # Optional Docker build file
├── README.md                      # Full technical documentation
├── IMPROVEMENTS.md                # Detailed improvements list
├── .env.example                   # Environment variables template
│
├── config/                        # Configuration files directory
│   ├── nginx.conf                 # Nginx web server configuration
│   ├── php-fpm.conf               # PHP-FPM runtime configuration
│   ├── supervisord.conf           # Supervisor main configuration
│   │
│   └── workers/                   # Individual process worker configs
│       ├── nginx.conf             # Nginx supervisor worker
│       ├── php-fpm.conf           # PHP-FPM supervisor worker
│       └── laravel-queue-worker.conf  # Queue worker (disabled by default)
│
└── scripts/                       # Runtime scripts
    └── start.sh                   # Container initialization script
```

## Important Notes

### File Locations
- **nixpacks.toml**: Located both in `/nixpacks/` and repository root (for Nixpacks detection)
- **All config files**: Embedded in `nixpacks.toml` as staticAssets
- **Actual files**: Copied to system locations by `start.sh` at runtime

### Path References
All paths in:
- `nixpacks.toml` → Uses `/nixpacks/` prefix
- `start.sh` → Uses `/nixpacks/` prefix
- Container runtime → Files copied from `/nixpacks/` to system locations

### How It Works
1. **Build Phase**: Composer installs dependencies
2. **Start Phase**: `start.sh` runs
3. **Initialization**: 
   - Creates system directories
   - Copies configs from `/nixpacks/` to `/etc/`
   - Runs Laravel setup
   - Starts Supervisor
4. **Runtime**: All services managed by Supervisor

## Deployment Steps

### Step 1: Place Files
```bash
# Copy entire nixpacks folder to your project root
cp -r nixpacks/ /path/to/your/project/

# Also ensure nixpacks.toml is in project root
cp nixpacks/nixpacks.toml /path/to/your/project/
```

### Step 2: Update Configuration
```bash
# Copy environment template
cp nixpacks/.env.example .env

# Edit with your values
nano .env
```

### Step 3: Deploy
```bash
git add .
git commit -m "Add/Update nixpacks configuration"
git push origin deploy
```

## Configuration Reference

### nixpacks.toml
- **Location**: Root of project (Nixpacks requires it there)
- **Content**: Complete build and runtime configuration
- **Config Files**: Embedded as `staticAssets` section
- **Size**: ~9KB

### config/nginx.conf
- **Purpose**: Nginx web server configuration
- **Embedded in**: nixpacks.toml as staticAsset
- **Deployed to**: `/etc/nginx/nginx.conf`
- **Features**: Security headers, caching, PHP-FPM integration

### config/php-fpm.conf
- **Purpose**: PHP-FPM runtime configuration
- **Embedded in**: nixpacks.toml as staticAsset
- **Deployed to**: `/etc/php-fpm/php-fpm.conf`
- **Features**: Dynamic process management, memory settings, logging

### config/supervisord.conf
- **Purpose**: Supervisor main configuration
- **Embedded in**: nixpacks.toml as staticAsset
- **Deployed to**: `/etc/supervisord.conf`
- **Features**: Foreground mode, log aggregation, process management

### config/workers/*.conf
- **nginx.conf**: Manages Nginx process (RUNNING)
- **php-fpm.conf**: Manages PHP-FPM process (RUNNING)
- **laravel-queue-worker.conf**: Manages queue workers (STOPPED by default)
- **Embedded in**: nixpacks.toml as staticAssets
- **Deployed to**: `/etc/supervisor/conf.d/`

### scripts/start.sh
- **Purpose**: Container initialization script
- **Embedded in**: nixpacks.toml as staticAsset
- **Runs**: When container starts (first thing)
- **Tasks**:
  1. Creates required directories
  2. Copies configs from `/nixpacks/` to system locations
  3. Runs Laravel initialization (migrations, caching)
  4. Starts Supervisor in foreground

## Customization

### Modify Nginx Configuration
1. Edit: `config/nginx.conf`
2. Update: `nixpacks.toml` (copy new content to staticAssets section)
3. Redeploy

### Modify PHP-FPM Settings
1. Edit: `config/php-fpm.conf`
2. Update: `nixpacks.toml` (copy new content to staticAssets section)
3. Redeploy

### Enable Queue Workers
1. Edit: `config/workers/laravel-queue-worker.conf`
2. Change: `autostart = false` → `autostart = true`
3. Update: `nixpacks.toml` (copy new content to staticAssets section)
4. Redeploy

## Troubleshooting

### Config Files Not Found
```bash
# Check if files exist in container
docker exec <container> ls -la /nixpacks/config/

# Check if deployed to system
docker exec <container> ls -la /etc/supervisor/conf.d/
```

### Supervisor Not Starting
```bash
# Check Supervisor status
docker exec <container> supervisorctl status

# Check Supervisor logs
docker logs <container> | grep supervisord
```

### Start Script Errors
```bash
# Check script permissions
ls -la /nixpacks/scripts/start.sh
# Should be executable (755)

# Run script manually
docker exec <container> /nixpacks/scripts/start.sh
```

## Environment Variables

Create `.env` file in project root:

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxx

# Database
DB_HOST=mysql.local
DB_DATABASE=laravel
DB_USERNAME=user
DB_PASSWORD=password

# Queue (if enabled)
QUEUE_CONNECTION=redis
REDIS_HOST=redis.local
```

## Support and Documentation

- **Full Documentation**: See `README.md` in this folder
- **Improvements List**: See `IMPROVEMENTS.md` in this folder
- **Environment Template**: See `.env.example` in this folder

## Version Information

- **Version**: 1.0 Production Ready
- **Date**: November 13, 2025
- **Compatibility**: Laravel 12+, Nixpacks, Docker
- **Status**: ✅ Ready for production deployment
