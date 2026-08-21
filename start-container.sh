#!/usr/bin/env bash
# ==============================================================================
# Container Entrypoint Script for E-BoardMate (Render Web Service)
# Note: Run `chmod +x start-container.sh` locally before committing to Git.
# ==============================================================================
set -e

echo "🚀 Running Laravel Production Caches & Migrations..."

# Cache Configurations, Routes, and Views for Maximum Speed
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run Concurrency-Safe Database Migrations
php artisan migrate --force

echo "⚡ Starting Apache Web Server in Foreground..."
exec apache2-foreground
