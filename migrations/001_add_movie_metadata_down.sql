-- Rollback Migration 001: Add Movie Metadata Columns
-- Removes the metadata columns added in migration 001

BEGIN;

-- Drop check constraints
ALTER TABLE movies
DROP CONSTRAINT IF EXISTS check_release_year,
DROP CONSTRAINT IF EXISTS check_runtime,
DROP CONSTRAINT IF EXISTS check_imdb_rating,
DROP CONSTRAINT IF EXISTS check_user_rating,
DROP CONSTRAINT IF EXISTS check_votes_count;

-- Drop added columns
ALTER TABLE movies 
DROP COLUMN IF EXISTS release_year,
DROP COLUMN IF EXISTS director,
DROP COLUMN IF EXISTS actors,
DROP COLUMN IF EXISTS runtime_minutes,
DROP COLUMN IF EXISTS poster_url,
DROP COLUMN IF EXISTS backdrop_url,
DROP COLUMN IF EXISTS imdb_rating,
DROP COLUMN IF EXISTS user_rating,
DROP COLUMN IF EXISTS votes_count,
DROP COLUMN IF EXISTS updated_at;

COMMIT;

SELECT 'Migration 001 rolled back: Movie metadata columns removed' as status;
