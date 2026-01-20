# Database Migrations for Phase 2

This directory contains all database migrations for the Movie Suggestor Phase 2 upgrade.

## Overview

Phase 2 introduces enhanced functionality including:
- Enhanced movie metadata (release year, director, actors, runtime, posters, ratings)
- User favorites tracking
- Watch later list functionality
- User ratings and reviews system
- Performance indexes for advanced filtering

## Migration Files

| Migration | Description | Status |
|-----------|-------------|--------|
| `000_migration_tracking.sql` | Creates migration history tracking table | System |
| `001_add_movie_metadata.sql` | Adds metadata columns to movies table | Ready |
| `002_create_favorites_table.sql` | Creates favorites table | Ready |
| `003_create_watch_later_table.sql` | Creates watch_later table | Ready |
| `004_create_ratings_table.sql` | Creates ratings table | Ready |
| `005_create_indexes.sql` | Adds performance indexes | Ready |

Each migration has a corresponding `*_down.sql` file for rollback capability.

## Usage

### Run All Pending Migrations
```bash
php migrations/run-migrations.php
# or
php migrations/run-migrations.php up
```

### Check Migration Status
```bash
php migrations/run-migrations.php status
```

### Validate Migration Files (Without Running)
```bash
php migrations/run-migrations.php validate
```

### Rollback Last Migration
```bash
php migrations/run-migrations.php down
```

### Rollback All Migrations
```bash
php migrations/run-migrations.php down all
```

## Migration Runner Features

- ✓ **Automatic Tracking**: Tracks which migrations have been applied
- ✓ **Execution Timing**: Records how long each migration takes
- ✓ **Error Handling**: Stops on first error to prevent partial migrations
- ✓ **Rollback Support**: Can rollback individual or all migrations
- ✓ **Status Reporting**: Shows which migrations are pending/applied
- ✓ **Validation**: Check SQL files before running

## Database Changes

### Migration 001: Movie Metadata
Adds the following columns to `movies` table:
- `release_year` - Year the movie was released (INT)
- `director` - Primary director name (VARCHAR 255)
- `actors` - Comma-separated list of main actors (TEXT)
- `runtime_minutes` - Movie runtime in minutes (INT)
- `poster_url` - URL to movie poster image (VARCHAR 500)
- `backdrop_url` - URL to backdrop/banner image (VARCHAR 500)
- `imdb_rating` - Official IMDB rating 0.0-10.0 (DECIMAL 3,1)
- `user_rating` - Calculated average user rating (DECIMAL 3,1)
- `votes_count` - Number of user ratings (INT)
- `updated_at` - Record update timestamp (TIMESTAMP)

Includes CHECK constraints for data validation.

### Migration 002: Favorites Table
Creates `favorites` table with:
- `id` - Primary key
- `user_id` - User who favorited (INT)
- `movie_id` - Foreign key to movies.id
- `created_at` - When favorited
- Unique constraint on (user_id, movie_id)
- Indexes for performance

### Migration 003: Watch Later Table
Creates `watch_later` table with:
- `id` - Primary key
- `user_id` - User who added item (INT)
- `movie_id` - Foreign key to movies.id
- `watched` - Boolean flag for watched status
- `added_at` - When added to list
- `watched_at` - When marked as watched
- Unique constraint on (user_id, movie_id)
- Indexes for filtering watched/unwatched

### Migration 004: Ratings Table
Creates `ratings` table with:
- `id` - Primary key
- `user_id` - User who rated (INT)
- `movie_id` - Foreign key to movies.id
- `rating` - Rating value 0.0-10.0 (DECIMAL 3,1)
- `review` - Optional review text (TEXT)
- `created_at` - When created
- `updated_at` - When last updated
- Unique constraint on (user_id, movie_id)
- CHECK constraint for rating range
- Indexes for performance

### Migration 005: Performance Indexes
Adds indexes to `movies` table for:
- Category + Score filtering
- Release year filtering
- Runtime filtering
- User rating sorting
- Title search
- Composite indexes for common queries
- Full-text search on title and description

## Safety Features

1. **Transactions**: All migrations run in transactions (BEGIN/COMMIT)
2. **IF EXISTS Checks**: Tables created with IF NOT EXISTS
3. **Constraint Naming**: All constraints explicitly named for easy rollback
4. **Foreign Keys**: Cascade deletes to maintain referential integrity
5. **Unique Constraints**: Prevent duplicate user actions
6. **Check Constraints**: Validate data ranges and values

## Pre-Migration Checklist

- [ ] Database backup created
- [ ] Local testing environment ready
- [ ] All Phase 1 tests passing
- [ ] Migration files validated
- [ ] Database credentials configured

## Post-Migration Checklist

- [ ] All migrations completed successfully
- [ ] No errors in migration_history table
- [ ] New tables visible in database
- [ ] New columns added to movies table
- [ ] Indexes created successfully
- [ ] Foreign key constraints working

## Troubleshooting

### Migration Fails
1. Check error message in output
2. Check `migration_history` table for details
3. Review the failed migration SQL
4. Fix any database state issues
5. Rollback if needed: `php migrations/run-migrations.php down`

### Rollback Needed
```bash
# Rollback last migration
php migrations/run-migrations.php down

# Rollback all migrations
php migrations/run-migrations.php down all
```

### Check What's Applied
```bash
php migrations/run-migrations.php status
```

## Next Steps After Migration

1. Update repository classes (FavoritesRepository, WatchLaterRepository, etc.)
2. Update MovieRepository to use new metadata columns
3. Implement FilterBuilder for advanced queries
4. Add API endpoints for new features
5. Update frontend to use new data
6. Populate existing movies with metadata

## Technical Notes

- **Engine**: InnoDB (supports transactions and foreign keys)
- **Charset**: utf8mb4 (full Unicode support including emojis)
- **Primary Keys**: All tables use AUTO_INCREMENT integer IDs
- **Timestamps**: TIMESTAMP columns auto-update with CURRENT_TIMESTAMP
- **Indexes**: Composite indexes ordered for query optimization
- **Foreign Keys**: ON DELETE CASCADE for automatic cleanup

## Migration History Tracking

The `migration_history` table tracks:
- `migration_name` - Name of the migration
- `applied_at` - When it was applied
- `execution_time_ms` - How long it took
- `status` - success, failed, or rolled_back
- `error_message` - Any error details

This allows full audit trail of all database changes.
