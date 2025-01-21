# Laravel Web Application

## Installation Instructions

### Prerequisites
- PHP >= 8.1
- Composer
- MySQL or MariaDB
- Node.js & NPM

### Installation Steps

1. Clone the repository:
```bash
git clone https://github.com/tuhmaz/laravelweb.git
cd laravelweb
```

2. For Windows users, run the installation script:
```powershell
.\install.ps1
```

For Linux/Mac users:
```bash
chmod +x install.sh
./install.sh
```

### Manual Installation (if scripts don't work)

1. Copy the environment file:
```bash
cp .env.example .env
```

2. Configure your `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

3. Install dependencies:
```bash
composer install --no-interaction --prefer-dist --optimize-autoloader
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations:
```bash
php artisan migrate --force
```

6. Clear all caches:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

7. Optimize the application:
```bash
php artisan optimize
```

### Troubleshooting

If you encounter the error about the `settings` table not existing during installation:

1. First run the migrations:
```bash
php artisan migrate --force
```

2. Then proceed with the rest of the installation:
```bash
composer dump-autoload -o
php artisan package:discover
```

## License

This project is licensed under the MIT License.
