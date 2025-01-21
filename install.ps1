# Function to print messages
function Print-Message {
    param([string]$message)
    Write-Host ">>> $message" -ForegroundColor Green
}

# Copy .env file if it doesn't exist
if (-not (Test-Path .env)) {
    Print-Message "Creating .env file..."
    Copy-Item .env.example .env
    php artisan key:generate
}

# Install dependencies
Print-Message "Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Clear all caches
Print-Message "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
Print-Message "Running migrations..."
php artisan migrate --force

# Optimize the application
Print-Message "Optimizing the application..."
php artisan optimize

# Final steps
Print-Message "Running package discovery..."
composer dump-autoload -o
php artisan package:discover

Print-Message "Installation completed!"
