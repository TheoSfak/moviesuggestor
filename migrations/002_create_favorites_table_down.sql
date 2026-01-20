-- Rollback Migration 002: Create Favorites Table
-- Drops the favorites table

BEGIN;

DROP TABLE IF EXISTS favorites;

COMMIT;

SELECT 'Migration 002 rolled back: Favorites table dropped' as status;
