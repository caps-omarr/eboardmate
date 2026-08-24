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

# 2. Ensure .env File Exists
if [ ! -f .env ]; then
    echo "📄 Creating .env file from .env.example..."
    cp .env.example .env
fi

# 3. Clear Build-Time Stale Cached Configuration
echo "🧹 Clearing stale config caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

# 4. Fallback Session & Cache Drivers (Prevents DB queries during session bootstrap)
export SESSION_DRIVER=${SESSION_DRIVER:-file}
export CACHE_STORE=${CACHE_STORE:-file}

# 6. Enable APP_DEBUG temporarily for diagnostic visibility on Render
export APP_DEBUG=true

# 6. Run Concurrency-Safe Database Migrations & Seeders
echo "🗄️ Executing database migrations..."
php artisan migrate --force || echo "⚠️ Database Migration Warning: Proceeding with container startup..."

echo "🌱 Seeding initial production data..."
php artisan db:seed --class=EBoardMateUserSeeder --force || echo "⚠️ Seeder Warning: Proceeding with container startup..."

# 7. Cache Configurations, Routes, and Views for Production Performance
echo "⚡ Caching Laravel production configurations..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "⚡ Starting Apache Web Server in Foreground..."
exec apache2-foreground
