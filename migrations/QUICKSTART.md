# 🚀 Quick Start: Run Phase 2 Migrations

## One-Command Deployment

```bash
php migrations/run-migrations.php
```

That's it! The script will:
1. ✓ Connect to your database
2. ✓ Create migration tracking table
3. ✓ Run all 5 pending migrations
4. ✓ Record execution details
5. ✓ Report success/failure

## Expected Output

```
======================================================================
  Running Database Migrations
======================================================================

→ Running: 001_add_movie_metadata ... ✓ (45 ms)
→ Running: 002_create_favorites_table ... ✓ (32 ms)
→ Running: 003_create_watch_later_table ... ✓ (28 ms)
→ Running: 004_create_ratings_table ... ✓ (35 ms)
→ Running: 005_create_indexes ... ✓ (67 ms)

✓ All migrations completed successfully!
  Applied: 5 migration(s)
```

## Other Useful Commands

### Check What Will Run (Dry-Run)
```bash
php migrations/run-migrations.php status
```

### Validate Before Running
```bash
php migrations/run-migrations.php validate
# or on Windows:
.\migrations\validate.ps1
```

### Rollback If Needed
```bash
# Undo last migration
php migrations/run-migrations.php down

# Undo all migrations
php migrations/run-migrations.php down all
```

## Before You Run

### ✓ Checklist
- [ ] Database backup created
- [ ] Database credentials configured in `src/Database.php`
- [ ] PHP is installed and working
- [ ] MySQL/MariaDB is running
- [ ] You're in the project root directory

### Verify Database Connection
```bash
php validate-db.php
```

## What Gets Created

### 4 New Tables
1. `favorites` - User favorite movies
2. `watch_later` - User watch later lists
3. `ratings` - User movie ratings
4. `migration_history` - Migration tracking

### 10 New Columns in `movies`
- `release_year`
- `director`
- `actors`
- `runtime_minutes`
- `poster_url`
- `backdrop_url`
- `imdb_rating`
- `user_rating`
- `votes_count`
- `updated_at`

### 7 Performance Indexes
- Category + Score
- Release Year
- Runtime
- User Rating
- Title Search
- Composite Filters
- Full-Text Search

## If Something Goes Wrong

### Error During Migration?
The script automatically stops and shows the error. Nothing after the failed migration will run.

### Need to Rollback?
```bash
php migrations/run-migrations.php down
```

### Check What Happened?
```bash
php migrations/run-migrations.php status
```

Shows:
- Which migrations are applied
- Which are pending
- Recent migration history
- Execution times
- Any errors

## Post-Migration Verification

### 1. Check Tables Created
```sql
SHOW TABLES;
```
Should show: `favorites`, `watch_later`, `ratings`, `migration_history`

### 2. Check Movies Columns
```sql
DESCRIBE movies;
```
Should show 10 new columns

### 3. Check Migration History
```sql
SELECT * FROM migration_history ORDER BY applied_at DESC;
```
Should show 5 successful migrations

## Troubleshooting

### "php: command not found"
PHP is not in your PATH. Either:
1. Install PHP: https://www.php.net/downloads
2. Use full path: `C:\php\php.exe migrations/run-migrations.php`

### "Connection refused"
Database is not running. Start your MySQL/MariaDB server.

### "Access denied for user"
Database credentials are wrong. Update `src/Database.php`

### "Table 'movies' doesn't exist"
Run the base schema first:
```bash
# Import base schema
mysql -u root -p moviesuggestor < schema.sql
```

## Success!

After migrations run successfully:
1. ✓ Database is upgraded to Phase 2 schema
2. ✓ Ready for Phase 2 repository classes
3. ✓ Ready for Phase 2 API endpoints
4. ✓ Ready for Phase 2 features

## Next Steps

1. Activate Phase 2 repositories (from `phase2-prep/templates/`)
2. Populate movie metadata
3. Test new features
4. Deploy to production

---

**Need Help?** Check [migrations/README.md](README.md) for detailed documentation.
