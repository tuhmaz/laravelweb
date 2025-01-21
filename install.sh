#!/bin/bash

# Function to print messages
print_message() {
    echo ">>> $1"
}

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    print_message "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Install dependencies
print_message "Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Clear all caches
print_message "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
print_message "Running migrations..."
php artisan migrate --force

# Optimize the application
print_message "Optimizing the application..."
php artisan optimize

# Final steps
print_message "Running package discovery..."
composer dump-autoload -o
php artisan package:discover

print_message "Installation completed!"
