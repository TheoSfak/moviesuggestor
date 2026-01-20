-- Rollback Migration 005: Create Performance Indexes
-- Drops the performance indexes

BEGIN;

-- Drop indexes from movies table
ALTER TABLE movies
DROP INDEX IF EXISTS idx_category_score,
DROP INDEX IF EXISTS idx_release_year,
DROP INDEX IF EXISTS idx_runtime,
DROP INDEX IF EXISTS idx_user_rating,
DROP INDEX IF EXISTS idx_title_search,
DROP INDEX IF EXISTS idx_category_year_score,
DROP INDEX IF EXISTS idx_fulltext_search;

COMMIT;

SELECT 'Migration 005 rolled back: Performance indexes dropped' as status;
