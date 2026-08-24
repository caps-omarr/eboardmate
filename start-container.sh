#!/usr/bin/env bash
# ==============================================================================
# Container Entrypoint Script for E-BoardMate (Render Web Service)
# Note: Run `chmod +x start-container.sh` locally before committing to Git.
# ==============================================================================
set -e

echo "🚀 Starting E-BoardMate Production Startup..."

# Clear stale cached configuration & routes
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run Concurrency-Safe Database Migrations
echo "🗄️ Executing database migrations..."
php artisan migrate --force || echo "⚠️ Database Migration Warning: Proceeding with container startup..."

# Cache Configurations, Routes, and Views for Maximum Speed
echo "⚡ Caching Laravel production configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "⚡ Starting Apache Web Server in Foreground..."
exec apache2-foreground
