# Movie Suggestor

A PHP + MySQL web application that suggests movies to users based on category and minimum score.

## Features

- ✅ Filter movies by category
- ✅ Filter movies by minimum score
- ✅ View movie details with trailer links
- ✅ Graceful handling of empty results
- ✅ Clean, responsive UI

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
├── src/
│   ├── Database.php         # Database connection handler
│   └── MovieRepository.php  # Movie data access layer
├── tests/
│   └── MovieRepositoryTest.php  # PHPUnit tests
├── index.php                # Main application UI
├── schema.sql               # Database schema and sample data
├── composer.json            # PHP dependencies
├── phpunit.xml              # PHPUnit configuration
├── JUDGE_RULES.md           # Judge evaluation criteria
└── .github/
    └── workflows/
        └── judge.yml        # CI/CD workflow
```

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
