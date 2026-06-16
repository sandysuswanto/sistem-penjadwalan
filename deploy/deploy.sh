#!/bin/bash
# ==============================================================================
# Laravel 13 Production Auto-Deployment Script
# ==============================================================================
#
# INSTRUCTIONS:
# 1. Place this file in your deploy/ directory.
# 2. Make it executable: chmod +x deploy/deploy.sh
# 3. Configure passwordless sudo for www-data or your deploy user to restart services:
#    e.g. Add this to /etc/sudoers (via 'sudo visudo'):
#    %www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm, /usr/bin/systemctl restart laravel-worker.service
# 4. Run the script: ./deploy/deploy.sh
#

# Exit immediately if a command exits with a non-zero status
set -e

# --- CONFIGURATION ---
APP_DIR="/var/www/sistem-penjadwalan"
BRANCH="main" # change to your deployment branch (e.g. master, production)
PHP_SERVICE="php8.3-fpm"
WORKER_SERVICE="laravel-worker.service"
# --------------------

echo "=== Starting deployment for sistem-penjadwalan ==="
cd "$APP_DIR"

# Ensure we are in the correct directory
if [ ! -f "artisan" ]; then
    echo "Error: artisan file not found. Make sure APP_DIR is configured correctly."
    exit 1
fi

# 1. Put Laravel into Maintenance Mode
echo "🚧 Putting application in maintenance mode..."
php artisan down --render="errors::503" --retry=60 || true

# 2. Pull latest changes from Git
echo "📥 Fetching latest code changes from Git ($BRANCH)..."
git fetch origin
git checkout "$BRANCH"
git pull origin "$BRANCH"

# 3. Install/update Composer dependencies (Production optimized)
echo "📦 Installing PHP dependencies..."
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 4. Install NPM dependencies & build Vite assets
echo "⚡ Building frontend assets (Vite)..."
if [ -f "package.json" ]; then
    npm ci --ignore-scripts || npm install
    npm run build
fi

# 5. Run Database Migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 6. Optimize Configuration Cache
echo "🚀 Caching Laravel configurations and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Reload PHP-FPM to clear OPCache
echo "🔄 Reloading PHP-FPM to flush OPcache..."
if systemctl is-active --quiet "$PHP_SERVICE"; then
    sudo systemctl reload "$PHP_SERVICE"
else
    echo "Warning: $PHP_SERVICE is not running or sudo permissions are missing."
fi

# 8. Restart Laravel Worker (Systemd)
echo "⚙️ Restarting Queue Worker..."
if systemctl is-active --quiet "$WORKER_SERVICE"; then
    sudo systemctl restart "$WORKER_SERVICE"
else
    echo "Warning: $WORKER_SERVICE is not running or sudo permissions are missing."
fi

# 9. Bring Application Back Online
echo "✅ Bringing application online..."
php artisan up

echo "=== Deployment Completed Successfully! ==="
