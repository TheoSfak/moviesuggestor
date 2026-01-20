-- =====================================================
-- Migration 007: TMDB Integration
-- Converts app to use TMDB as primary movie source
-- =====================================================

-- Add TMDB columns to favorites table
ALTER TABLE favorites
ADD COLUMN tmdb_id INT NOT NULL DEFAULT 0 AFTER movie_id,
ADD COLUMN movie_title VARCHAR(255) DEFAULT NULL,
ADD COLUMN poster_url VARCHAR(500) DEFAULT NULL,
ADD COLUMN release_year INT DEFAULT NULL,
ADD COLUMN category VARCHAR(100) DEFAULT NULL,
ADD INDEX idx_tmdb_id (tmdb_id),
ADD INDEX idx_user_tmdb (user_id, tmdb_id);

-- Add TMDB columns to watch_later table
ALTER TABLE watch_later
ADD COLUMN tmdb_id INT NOT NULL DEFAULT 0 AFTER movie_id,
ADD COLUMN movie_title VARCHAR(255) DEFAULT NULL,
ADD COLUMN poster_url VARCHAR(500) DEFAULT NULL,
ADD COLUMN release_year INT DEFAULT NULL,
ADD COLUMN category VARCHAR(100) DEFAULT NULL,
ADD INDEX idx_tmdb_id (tmdb_id),
ADD INDEX idx_user_tmdb (user_id, tmdb_id);

-- Add TMDB columns to ratings table
ALTER TABLE ratings
ADD COLUMN tmdb_id INT NOT NULL DEFAULT 0 AFTER movie_id,
ADD COLUMN movie_title VARCHAR(255) DEFAULT NULL,
ADD COLUMN poster_url VARCHAR(500) DEFAULT NULL,
ADD COLUMN release_year INT DEFAULT NULL,
ADD COLUMN category VARCHAR(100) DEFAULT NULL,
ADD INDEX idx_tmdb_id (tmdb_id),
ADD INDEX idx_user_tmdb (user_id, tmdb_id);

-- Note: movie_id columns kept for backward compatibility
-- But tmdb_id is now the primary identifier for user actions
-- The movies table remains for imported movies only (optional)
