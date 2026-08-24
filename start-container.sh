#!/usr/bin/env bash
# ==============================================================================
# Container Entrypoint Script for E-BoardMate (Render Web Service)
# Note: Run `chmod +x start-container.sh` locally before committing to Git.
# ==============================================================================
set -e

echo "🚀 Starting E-BoardMate Production Startup..."

# 1. Ensure Storage Directories & Ownership Permissions
echo "📁 Setting up storage directory permissions..."
mkdir -p /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 2. Clear Build-Time Stale Cached Configuration
echo "🧹 Clearing stale config caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

# 3. Fail-Safe APP_KEY Verification & Auto-Generation
if [ -z "$APP_KEY" ]; then
    echo "⚠️ APP_KEY environment variable is empty! Auto-generating production application key..."
    export APP_KEY=$(php artisan key:generate --show)
fi

# 4. Run Concurrency-Safe Database Migrations
echo "🗄️ Executing database migrations..."
php artisan migrate --force || echo "⚠️ Database Migration Warning: Proceeding with container startup..."

# 5. Cache Configurations, Routes, and Views for Production Performance
echo "⚡ Caching Laravel production configurations..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "⚡ Starting Apache Web Server in Foreground..."
exec apache2-foreground
