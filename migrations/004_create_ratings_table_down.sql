-- Rollback Migration 004: Create Ratings Table
-- Drops the ratings table

BEGIN;

DROP TABLE IF EXISTS ratings;

COMMIT;

SELECT 'Migration 004 rolled back: Ratings table dropped' as status;
