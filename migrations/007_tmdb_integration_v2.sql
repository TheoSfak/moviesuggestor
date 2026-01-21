-- =====================================================
-- Migration 007: TMDB Integration (FIXED)
-- Converts app to use TMDB as primary movie source
-- Checks for existing columns before adding
-- =====================================================

-- Add TMDB columns to favorites table (only if they don't exist)
SET @favorites_tmdb_check = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'favorites' 
    AND COLUMN_NAME = 'tmdb_id'
);

SET @sql = IF(@favorites_tmdb_check = 0,
    'ALTER TABLE favorites
    ADD COLUMN tmdb_id INT NOT NULL DEFAULT 0 AFTER movie_id,
    ADD COLUMN movie_title VARCHAR(255) DEFAULT NULL,
    ADD COLUMN poster_url VARCHAR(500) DEFAULT NULL,
    ADD COLUMN release_year INT DEFAULT NULL,
    ADD COLUMN category VARCHAR(100) DEFAULT NULL,
    ADD INDEX idx_tmdb_id (tmdb_id),
    ADD INDEX idx_user_tmdb (user_id, tmdb_id)',
    'SELECT "Favorites table already has TMDB columns" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add TMDB columns to watch_later table (only if they don't exist)
SET @watchlater_tmdb_check = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'watch_later' 
    AND COLUMN_NAME = 'tmdb_id'
);

SET @sql = IF(@watchlater_tmdb_check = 0,
    'ALTER TABLE watch_later
    ADD COLUMN tmdb_id INT NOT NULL DEFAULT 0 AFTER movie_id,
    ADD COLUMN movie_title VARCHAR(255) DEFAULT NULL,
    ADD COLUMN poster_url VARCHAR(500) DEFAULT NULL,
    ADD COLUMN release_year INT DEFAULT NULL,
    ADD COLUMN category VARCHAR(100) DEFAULT NULL,
    ADD INDEX idx_tmdb_id (tmdb_id),
    ADD INDEX idx_user_tmdb (user_id, tmdb_id)',
    'SELECT "Watch later table already has TMDB columns" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add TMDB columns to ratings table (only if they don't exist)
SET @ratings_tmdb_check = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'ratings' 
    AND COLUMN_NAME = 'tmdb_id'
);

SET @sql = IF(@ratings_tmdb_check = 0,
    'ALTER TABLE ratings
    ADD COLUMN tmdb_id INT NOT NULL DEFAULT 0 AFTER movie_id,
    ADD COLUMN movie_title VARCHAR(255) DEFAULT NULL,
    ADD COLUMN poster_url VARCHAR(500) DEFAULT NULL,
    ADD COLUMN release_year INT DEFAULT NULL,
    ADD COLUMN category VARCHAR(100) DEFAULT NULL,
    ADD INDEX idx_tmdb_id (tmdb_id),
    ADD INDEX idx_user_tmdb (user_id, tmdb_id)',
    'SELECT "Ratings table already has TMDB columns" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Note: movie_id columns kept for backward compatibility
-- But tmdb_id is now the primary identifier for user actions
-- The movies table remains for imported movies only (optional)
