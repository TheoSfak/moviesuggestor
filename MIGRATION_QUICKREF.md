# 🗄️ DATABASE MIGRATION - QUICK REFERENCE

## STEP 1: RUN MIGRATION

### Option A: From XAMPP Directory (Recommended)
```bash
cd c:\xampp\htdocs\moviesuggestor
mysql -u root moviesuggestor < migrations\007_tmdb_integration.sql
```

### Option B: From Desktop Directory
```bash
cd c:\Users\user\Desktop\moviesuggestor
mysql -u root moviesuggestor < migrations\007_tmdb_integration.sql
```

### Option C: MySQL Command Line
```sql
USE moviesuggestor;
SOURCE c:/xampp/htdocs/moviesuggestor/migrations/007_tmdb_integration.sql;
```

---

## STEP 2: VERIFY MIGRATION

```sql
USE moviesuggestor;

-- Check if tmdb_id column was added
SHOW COLUMNS FROM favorites WHERE Field = 'tmdb_id';
SHOW COLUMNS FROM watch_later WHERE Field = 'tmdb_id';
SHOW COLUMNS FROM ratings WHERE Field = 'tmdb_id';

-- Should show: tmdb_id | int | NO | MUL | 0
```

Expected output:
```
+---------+------+------+-----+---------+-------+
| Field   | Type | Null | Key | Default | Extra |
+---------+------+------+-----+---------+-------+
| tmdb_id | int  | NO   | MUL | 0       |       |
+---------+------+------+-----+---------+-------+
```

---

## STEP 3: CHECK INDEXES

```sql
-- Check indexes on favorites
SHOW INDEXES FROM favorites WHERE Key_name LIKE 'idx_%';

-- Check indexes on watch_later
SHOW INDEXES FROM watch_later WHERE Key_name LIKE 'idx_%';

-- Check indexes on ratings
SHOW INDEXES FROM ratings WHERE Key_name LIKE 'idx_%';
```

You should see:
- `idx_tmdb_id` - Index on tmdb_id column
- `idx_user_tmdb` - Composite index on (user_id, tmdb_id)

---

## STEP 4: VIEW TABLE STRUCTURE

```sql
-- See all columns in favorites
DESCRIBE favorites;

-- Expected columns:
-- id (PK)
-- user_id
-- movie_id (old, kept for compatibility)
-- tmdb_id (NEW)
-- movie_title (NEW)
-- poster_url (NEW)
-- release_year (NEW)
-- category (NEW)
-- created_at
```

---

## WHAT THE MIGRATION DOES

### For Each Table (favorites, watch_later, ratings):

1. ✅ Adds `tmdb_id` column (INT, NOT NULL, DEFAULT 0)
2. ✅ Adds `movie_title` column (VARCHAR 255)
3. ✅ Adds `poster_url` column (VARCHAR 500)
4. ✅ Adds `release_year` column (INT)
5. ✅ Adds `category` column (VARCHAR 100)
6. ✅ Creates index on `tmdb_id`
7. ✅ Creates composite index on `(user_id, tmdb_id)`

**Note:** `movie_id` column is NOT removed for backward compatibility

---

## ROLLBACK (If Needed)

To undo the migration:

```sql
USE moviesuggestor;

-- Remove columns from favorites
ALTER TABLE favorites
DROP COLUMN tmdb_id,
DROP COLUMN movie_title,
DROP COLUMN poster_url,
DROP COLUMN release_year,
DROP COLUMN category,
DROP INDEX idx_tmdb_id,
DROP INDEX idx_user_tmdb;

-- Repeat for watch_later
ALTER TABLE watch_later
DROP COLUMN tmdb_id,
DROP COLUMN movie_title,
DROP COLUMN poster_url,
DROP COLUMN release_year,
DROP COLUMN category,
DROP INDEX idx_tmdb_id,
DROP INDEX idx_user_tmdb;

-- Repeat for ratings
ALTER TABLE ratings
DROP COLUMN tmdb_id,
DROP COLUMN movie_title,
DROP COLUMN poster_url,
DROP COLUMN release_year,
DROP COLUMN category,
DROP INDEX idx_tmdb_id,
DROP INDEX idx_user_tmdb;
```

---

## TESTING QUERIES

### Test Adding TMDB Movie to Favorites

```sql
-- Example: Add "The Matrix" (TMDB ID: 603) to favorites
INSERT INTO favorites 
(user_id, tmdb_id, movie_title, poster_url, release_year, category, created_at)
VALUES 
(1, 603, 'The Matrix', 'https://image.tmdb.org/t/p/w500/path.jpg', 1999, 'Επιστημονική Φαντασία', NOW());

-- Verify
SELECT user_id, tmdb_id, movie_title, release_year, category 
FROM favorites 
WHERE user_id = 1;
```

### Test Watch Later

```sql
-- Add to watch later
INSERT INTO watch_later 
(user_id, tmdb_id, movie_title, poster_url, release_year, category, added_at)
VALUES 
(1, 550, 'Fight Club', 'https://image.tmdb.org/t/p/w500/path.jpg', 1999, 'Δράμα', NOW());

-- Verify
SELECT * FROM watch_later WHERE user_id = 1;
```

### Test Ratings

```sql
-- Add rating
INSERT INTO ratings 
(user_id, tmdb_id, rating, movie_title, poster_url, release_year, category, created_at)
VALUES 
(1, 27205, 'Inception', 'https://image.tmdb.org/t/p/w500/path.jpg', 2010, 'Επιστημονική Φαντασία', NOW());

-- Verify
SELECT user_id, tmdb_id, rating, movie_title 
FROM ratings 
WHERE user_id = 1;
```

---

## COMMON ISSUES

### Issue: "Access denied for user 'root'"
**Solution:** 
```bash
# Add password
mysql -u root -p moviesuggestor < migrations\007_tmdb_integration.sql
# Enter password when prompted
```

### Issue: "Database 'moviesuggestor' doesn't exist"
**Solution:**
```sql
CREATE DATABASE IF NOT EXISTS moviesuggestor;
USE moviesuggestor;
SOURCE migrations/007_tmdb_integration.sql;
```

### Issue: "Table 'favorites' doesn't exist"
**Solution:** Run previous migrations first:
```bash
mysql -u root moviesuggestor < migrations\002_create_favorites_table.sql
mysql -u root moviesuggestor < migrations\003_create_watch_later_table.sql
mysql -u root moviesuggestor < migrations\004_create_ratings_table.sql
# Then run 007
mysql -u root moviesuggestor < migrations\007_tmdb_integration.sql
```

### Issue: "Duplicate column name 'tmdb_id'"
**Solution:** Migration already ran! Skip this step.
```sql
-- Check if columns exist
SHOW COLUMNS FROM favorites WHERE Field = 'tmdb_id';
-- If it returns a row, you're good!
```

---

## VERIFY EVERYTHING WORKS

```sql
USE moviesuggestor;

-- 1. Check table structures
DESCRIBE favorites;
DESCRIBE watch_later;
DESCRIBE ratings;

-- 2. Check indexes
SHOW INDEXES FROM favorites;
SHOW INDEXES FROM watch_later;
SHOW INDEXES FROM ratings;

-- 3. Test insert (then delete)
INSERT INTO favorites (user_id, tmdb_id, movie_title, category) 
VALUES (999, 12345, 'Test Movie', 'Test Category');

SELECT * FROM favorites WHERE user_id = 999;

DELETE FROM favorites WHERE user_id = 999;
```

If all queries run successfully, migration is complete! ✅

---

## PERFORMANCE CHECK

After migration, these queries should be fast:

```sql
-- Find favorite by TMDB ID (uses idx_tmdb_id)
SELECT * FROM favorites WHERE tmdb_id = 603;

-- Find user's favorite by TMDB ID (uses idx_user_tmdb)
SELECT * FROM favorites WHERE user_id = 1 AND tmdb_id = 603;

-- All should use indexes (check with EXPLAIN)
EXPLAIN SELECT * FROM favorites WHERE user_id = 1 AND tmdb_id = 603;
-- Should show "Using index" in Extra column
```

---

## SUMMARY

✅ Migration adds TMDB support to user tables
✅ Stores movie snapshots for quick display
✅ Maintains backward compatibility (movie_id kept)
✅ Adds performance indexes
✅ Ready for TMDB-powered app

**After running this migration, your app can store and retrieve movies using TMDB IDs!**
