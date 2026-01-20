-- Migration 005: Create Performance Indexes
-- Adds indexes to optimize Phase 2 query performance.

BEGIN;

-- Indexes for movies table (Phase 2 filtering)
ALTER TABLE movies
ADD INDEX idx_category_score (category, score DESC),
ADD INDEX idx_release_year (release_year DESC),
ADD INDEX idx_runtime (runtime_minutes),
ADD INDEX idx_user_rating (user_rating DESC, votes_count DESC),
ADD INDEX idx_title_search (title(50)),
ADD INDEX idx_category_year_score (category, release_year DESC, score DESC);

-- Full-text index for text search
ALTER TABLE movies
ADD FULLTEXT INDEX idx_fulltext_search (title, description);

COMMIT;

-- Migration completed successfully
SELECT 'Migration 005 completed: Performance indexes added' as status;
