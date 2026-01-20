# Setup Guide for Windows

Since Composer is not installed on your system, follow these steps:

## Prerequisites Installation

### 1. Install Composer (if not already installed)

Download and install Composer from: https://getcomposer.org/download/

Or use this PowerShell command:
```powershell
# Download installer
Invoke-WebRequest -Uri https://getcomposer.org/installer -OutFile composer-setup.php

# Run installer
php composer-setup.php --install-dir=C:\bin --filename=composer

# Add to PATH (restart terminal after)
# Or just use: php C:\bin\composer.phar instead of composer
```

### 2. Install MySQL (if not already installed)

Download MySQL from: https://dev.mysql.com/downloads/installer/

Or use XAMPP/WAMP which includes MySQL and PHP.

## Project Setup

Once Composer is installed:

```powershell
# Navigate to project
cd c:\Users\user\Desktop\moviesuggestor

# Install dependencies
composer install

# Create database
mysql -u root -p -e "CREATE DATABASE moviesuggestor;"
mysql -u root -p -e "CREATE DATABASE moviesuggestor_test;"

# Import schema
mysql -u root -p moviesuggestor < schema.sql
mysql -u root -p moviesuggestor_test < schema.sql

# Run tests
$env:DB_NAME="moviesuggestor_test"; vendor/bin/phpunit

# Start local server
php -S localhost:8000
```

## Alternative: Using XAMPP

If you have XAMPP installed:

1. Place this project in `C:\xampp\htdocs\moviesuggestor`
2. Start Apache and MySQL from XAMPP Control Panel
3. Open phpMyAdmin (http://localhost/phpmyadmin)
4. Create databases: `moviesuggestor` and `moviesuggestor_test`
5. Import `schema.sql` into both databases
6. Install composer dependencies
7. Access the app at http://localhost/moviesuggestor

## Ready for Judge

Once dependencies are installed and tests pass locally, you can:

1. Initialize git repository
2. Push to GitHub
3. The Judge workflow will automatically run

The Judge will verify:
- ✅ All PHPUnit tests pass
- ✅ No PHP syntax errors  
- ✅ Required files exist
- ✅ Database schema loads successfully
