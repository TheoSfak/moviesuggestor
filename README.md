# Movie Suggestor

A PHP + MySQL web application that suggests movies to users based on advanced filtering criteria and enables rich user interactions.

## ✨ Features

### Phase 1 - Core Features
- ✅ Filter movies by category
- ✅ Filter movies by minimum score
- ✅ View movie details with trailer links
- ✅ Graceful handling of empty results
- ✅ Clean, responsive UI

### Phase 2 - Advanced Features (NEW)
- ✅ **Advanced Filtering**: Multi-criteria filtering (category, score range, year, runtime, director, text search)
- ✅ **User Favorites**: Save and manage favorite movies
- ✅ **Watch Later**: Create a watch later list with watched status tracking
- ✅ **User Ratings**: Rate movies (0-10) with optional reviews
- ✅ **Enhanced Metadata**: Release year, director, actors, runtime, poster images, IMDB ratings
- ✅ **RESTful API**: Full API support for all user interaction features
- ✅ **Comprehensive Testing**: 199 tests with 491 assertions (100% pass rate)

## Tech Stack

- PHP 8.0+
- MySQL 8.0+
- PHPUnit 10 for testing
- Plain PHP (no framework)

## Setup Instructions

### 1. Install PHP and Composer

Make sure you have PHP 8.0+ and Composer installed on your system.

### 2. Install Dependencies

```bash
composer install
```

### 3. Setup Database

Create a MySQL database and import the schema:

```bash
mysql -u root -p -e "CREATE DATABASE moviesuggestor;"
mysql -u root -p moviesuggestor < schema.sql
```

### 3b. Run Migrations (Phase 2)

Apply database migrations for Phase 2 features:

```bash
php migrations/run-migrations.php
```

This will create:
- Enhanced movies table with metadata columns
- Favorites table for user favorites
- Watch later table for watch later functionality
- Ratings table for user reviews
- Optimized indexes for performance

### 4. Configure Database Connection

The application uses environment variables for database configuration. You can set them in your environment or the code will use defaults:

- `DB_HOST` (default: localhost)
- `DB_NAME` (default: moviesuggestor)
- `DB_USER` (default: root)
- `DB_PASS` (default: empty)

### 5. Run the Application

Start a local PHP server:

```bash
php -S localhost:8000
```

Then open http://localhost:8000 in your browser.

### 6. Run Tests

For testing, set up a test database:

```bash
mysql -u root -p -e "CREATE DATABASE moviesuggestor_test;"
mysql -u root -p moviesuggestor_test < schema.sql
```

Run PHPUnit tests:

```bash
DB_NAME=moviesuggestor_test vendor/bin/phpunit
```

Or on Windows PowerShell:

```powershell
$env:DB_NAME="moviesuggestor_test"; vendor/bin/phpunit
```

## Project Structure

```
moviesuggestor/
├── api/                     # RESTful API endpoints
│   ├── favorites.php        # Favorites management
│   ├── watch-later.php      # Watch later list
│   ├── ratings.php          # User ratings
│   └── README.md            # API documentation
├── src/                     # Core application classes
│   ├── Database.php         # Database connection handler
│   ├── MovieRepository.php  # Movie data access layer
│   ├── FavoritesRepository.php    # Favorites management
│   ├── WatchLaterRepository.php   # Watch later functionality
│   ├── RatingRepository.php       # Ratings and reviews
│   └── FilterBuilder.php    # Advanced filtering system
├── tests/                   # PHPUnit test suite (199 tests)
│   ├── MovieRepositoryTest.php
│   ├── FavoritesRepositoryTest.php
│   ├── WatchLaterRepositoryTest.php
│   ├── RatingRepositoryTest.php
│   └── FilterBuilderTest.php
├── migrations/              # Database migration system
│   ├── 001_add_movie_metadata.sql
│   ├── 002_create_favorites_table.sql
│   ├── 003_create_watch_later_table.sql
│   ├── 004_create_ratings_table.sql
│   ├── 005_create_indexes.sql
│   └── run-migrations.php
├── index.php                # Main application UI
├── schema.sql               # Database schema and sample data
├── composer.json            # PHP dependencies
├── phpunit.xml              # PHPUnit configuration
├── JUDGE_RULES.md           # Judge evaluation criteria
├── PHASE2_COMPLETE.md       # Phase 2 completion report
└── .github/
    └── workflows/
        └── judge.yml        # CI/CD workflow
```

## API Documentation

The application provides RESTful API endpoints for all user interaction features:

- **Favorites API**: `/api/favorites.php` - Add, remove, and list favorite movies
- **Watch Later API**: `/api/watch-later.php` - Manage watch later list and track watched movies
- **Ratings API**: `/api/ratings.php` - Rate movies and write reviews

Full API documentation available at `/api/` or see [api/README.md](api/README.md).

## Documentation

- [PHASE2_COMPLETE.md](PHASE2_COMPLETE.md) - Phase 2 completion report and feature summary
- [MIGRATION_VALIDATION_REPORT.md](MIGRATION_VALIDATION_REPORT.md) - Database migration details
- [PHASE2_TEST_SUMMARY.md](PHASE2_TEST_SUMMARY.md) - Comprehensive test coverage report
- [api/README.md](api/README.md) - API documentation and examples

## Development Workflow

This project follows a Judge-driven development approach:

1. Implement features incrementally
2. Write tests for each feature
3. Ensure tests pass locally
4. Push to GitHub
5. Wait for Judge workflow to pass (GitHub Actions)
6. Only proceed to next feature when Judge approves

## Judge Evaluation

The Judge workflow evaluates:
- ✓ All PHPUnit tests pass
- ✓ No PHP syntax errors
- ✓ Database schema exists
- ✓ Required files present
- ✓ Code quality standards

Check `.github/workflows/judge.yml` and `JUDGE_RULES.md` for details.

## Current Status

**Phase 1: Foundation** ✅ (Pending Judge approval)
- Database schema created
- Basic movie filtering implemented
- PHPUnit tests written
- CI/CD configured

**Phase 2: Core Features** (Next)
- Additional filtering options
- Enhanced UI
- More test coverage

**Phase 3: Robustness** (Future)
- Security hardening
- Edge case handling
- Performance optimization

## License

MIT
