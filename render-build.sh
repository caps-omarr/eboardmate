#!/usr/bin/env bash
# ==============================================================================
# Render Build Script for E-BoardMate (Laravel + Vue 3 Inertia PWA)
# Note: Run `chmod +x render-build.sh` locally before committing to Git.
# ==============================================================================
set -e

echo "🚀 Starting E-BoardMate Render Production Build..."

# 1. Install PHP Composer Dependencies (Production Optimized)
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# 2. Install Node.js NPM Dependencies & Compile Vite Assets
echo "⚡ Installing NPM packages and building Vue 3 / Inertia assets..."
npm install
npm run build

# 3. Run Concurrency-Safe Database Migrations
echo "🗄️ Executing database migrations..."
php artisan migrate --force

# 4. Cache Configurations, Routes, and Views for Maximum Speed
echo "⚡ Caching Laravel configurations and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🎉 Render Build Completed Successfully!"
