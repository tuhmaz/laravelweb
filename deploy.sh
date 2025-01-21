#!/bin/bash

# تحسين الأداء وتجميع الأصول
echo "🚀 Optimizing for production..."
yarn optimize

# تنظيف الكاش
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# إعادة بناء الكاش للإنتاج
echo "🔄 Rebuilding cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# تحسين autoloader
echo "📦 Optimizing Composer..."
composer install --optimize-autoloader --no-dev

# إنشاء رابط التخزين
php artisan storage:link

echo "✅ Project is ready for production!"
