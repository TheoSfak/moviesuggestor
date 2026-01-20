# Movie Suggestor - Installation Guide

## 📋 Table of Contents
- [Prerequisites](#prerequisites)
- [Installation Steps](#installation-steps)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Getting TMDB API Key](#getting-tmdb-api-key)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

---

## 🔧 Prerequisites

Before installing Movie Suggestor, ensure you have the following:

### Required Software
- **PHP 8.0 or higher** with extensions:
  - PDO
  - PDO_MySQL
  - cURL (recommended) or allow_url_fopen enabled
  - JSON
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Web Server**:
  - Apache 2.4+ with mod_rewrite enabled, OR
  - Nginx 1.18+
- **Composer** (PHP dependency manager)

### Optional
- **Git** (for version control)

---

## 📦 Installation Steps

### Step 1: Download/Clone the Project

**Option A: Download ZIP**
```bash
# Extract the ZIP file to your web server directory
# Example for XAMPP on Windows:
# Extract to: C:\xampp\htdocs\moviesuggestor
```

**Option B: Clone with Git**
```bash
# Clone the repository
git clone https://github.com/yourusername/moviesuggestor.git

# Navigate to project directory
cd moviesuggestor
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies with Composer
composer install
```

If you don't have Composer installed:
- Windows: Download from https://getcomposer.org/
- Linux/Mac: 
  ```bash
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
  ```

### Step 3: Set File Permissions (Linux/Mac)

```bash
# Make sure the web server can read all files
chmod -R 755 .

# Ensure writable directories (if any logs/cache)
chmod -R 775 logs cache
```

---

## ⚙️ Configuration

### Step 1: Create Environment File

Copy the example environment file:

```bash
# Windows (PowerShell)
Copy-Item .env.example .env

# Linux/Mac
cp .env.example .env
```

### Step 2: Edit .env File

Open `.env` in a text editor and configure:

```dotenv
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=moviesuggestor
DB_USER=root
DB_PASS=your_password_here

# TMDB API Configuration
# Get your free API key from: https://www.themoviedb.org/settings/api
TMDB_API_KEY=your_tmdb_api_key_here
```

**Important:** Replace the placeholder values with your actual credentials.

---

## 🗄️ Database Setup

### Step 1: Create Database

**Option A: Using MySQL Command Line**
```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE moviesuggestor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

**Option B: Using phpMyAdmin**
1. Open phpMyAdmin in your browser
2. Click "New" to create a database
3. Enter database name: `moviesuggestor`
4. Select collation: `utf8mb4_unicode_ci`
5. Click "Create"

### Step 2: Import Database Schema

**Option A: Using Command Line**
```bash
mysql -u root -p moviesuggestor < database-schema.sql
```

**Option B: Using phpMyAdmin**
1. Open phpMyAdmin
2. Select `moviesuggestor` database
3. Click "Import" tab
4. Choose file: `database-schema.sql`
5. Click "Go"

### Step 3: Verify Database

The schema includes these tables:
- `migration_history` - Migration tracking
- `movies` - Movie database (optional with TMDB)
- `favorites` - User favorite movies
- `watch_later` - Watch later list
- `ratings` - User ratings and reviews

---

## 🔑 Getting TMDB API Key

### Free API Key (Required)

1. **Create TMDB Account**
   - Go to https://www.themoviedb.org/signup
   - Sign up for a free account
   - Verify your email

2. **Request API Key**
   - Login to your account
   - Go to Settings > API
   - Click "Request an API Key"
   - Choose "Developer"
   
3. **Fill Application Form**
   - Application Name: Movie Suggestor (or your app name)
   - Application URL: http://localhost (or your domain)
   - Application Summary: Personal movie recommendation app
   - Accept the terms

4. **Copy Your API Key**
   - Copy the **API Key (v3 auth)**
   - Paste it in your `.env` file:
     ```
     TMDB_API_KEY=your_actual_api_key_here
     ```

### API Limits
- **Free Tier**: 40 requests per 10 seconds
- No cost for personal/non-commercial use
- More than sufficient for this application

---

## 🌐 Web Server Configuration

### Apache (.htaccess)

The project includes an `.htaccess` file for Apache. Make sure `mod_rewrite` is enabled:

```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo systemctl restart apache2

# CentOS/RHEL
# mod_rewrite is usually enabled by default
```

### Nginx Configuration

If using Nginx, add this to your server block:

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/moviesuggestor;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 🧪 Testing

### Step 1: Access the Application

Open your web browser and navigate to:
- **XAMPP**: http://localhost/moviesuggestor
- **Custom domain**: http://yourdomain.com

### Step 2: Verify Features

1. **Search Movies** - Try searching for "Inception"
2. **Filter by Category** - Select "Action" or "Drama"
3. **Add to Favorites** - Click the heart icon
4. **Rate a Movie** - Click stars to rate 1-10
5. **Watch Later** - Click the bookmark icon

### Step 3: Check Error Logs

If something doesn't work:

**Apache Error Log:**
- Windows (XAMPP): `C:\xampp\apache\logs\error.log`
- Linux: `/var/log/apache2/error.log`

**PHP Error Log:**
Check your `php.ini` for `error_log` location

---

## 🔧 Troubleshooting

### Common Issues

#### 1. "Database connection failed"
**Solution:**
- Verify database credentials in `.env`
- Ensure MySQL/MariaDB is running
- Check database exists: `SHOW DATABASES;`

#### 2. "TMDB API key not configured"
**Solution:**
- Verify `.env` file exists (not `.env.example`)
- Check TMDB_API_KEY is set correctly
- Ensure no spaces around the = sign

#### 3. "No movies found"
**Solution:**
- Verify TMDB API key is valid
- Check internet connection
- View browser console for API errors
- Check if curl extension is enabled: `php -m | grep curl`

#### 4. "Favorites/Ratings 500 Error"
**Solution:**
- Verify database schema is up to date
- Check migration 007 was applied (tmdb_id columns exist)
- Review PHP error logs

#### 5. "Movie descriptions showing 'No description available'"
**Solution:**
- This fix is included in the latest version
- Ensure you have the updated `TMDBService.php` and `index.php`

### Enable Debug Mode

To see detailed errors, edit `index.php`:

```php
// At the top of index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

**Remember to disable this in production!**

---

## 📚 Additional Resources

- **TMDB API Documentation**: https://developers.themoviedb.org/3
- **PHP Documentation**: https://www.php.net/manual/
- **MySQL Documentation**: https://dev.mysql.com/doc/

---

## 🎉 Success!

If you can see movies, filter them, and interact with favorites/ratings, your installation is complete!

### Next Steps:
1. Customize categories in TMDBService.php
2. Add more filtering options
3. Implement user authentication (optional)
4. Deploy to production server

---

## 📝 License

This project is for educational purposes. TMDB API usage must comply with their terms of service.

---

## 💬 Support

If you encounter issues:
1. Check the troubleshooting section above
2. Review error logs
3. Verify all prerequisites are met
4. Open an issue on GitHub (if applicable)

---

**Happy movie browsing! 🍿🎬**
