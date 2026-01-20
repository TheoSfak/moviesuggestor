-- Rollback Migration 003: Create Watch Later Table
-- Drops the watch_later table

BEGIN;

DROP TABLE IF EXISTS watch_later;

COMMIT;

SELECT 'Migration 003 rolled back: Watch later table dropped' as status;
