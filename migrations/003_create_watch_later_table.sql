-- Migration 003: Create Watch Later Table
-- Creates the watch_later table for Phase 2 user features.

BEGIN;

-- Create watch_later table
CREATE TABLE IF NOT EXISTS watch_later (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'User who added to watch later',
    movie_id INT NOT NULL COMMENT 'Reference to movies.id',
    watched BOOLEAN DEFAULT FALSE COMMENT 'Whether the movie has been watched',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When movie was added to list',
    watched_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When movie was marked as watched',
    
    -- Foreign key to movies table
    CONSTRAINT fk_watch_later_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    
    -- Ensure a user can add a movie only once
    UNIQUE KEY unique_user_movie_watch (user_id, movie_id),
    
    -- Indexes for efficient queries
    INDEX idx_user_unwatched (user_id, watched, added_at DESC),
    INDEX idx_user_watched (user_id, watched, watched_at DESC),
    INDEX idx_movie_watch_later (movie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User watch later list';

COMMIT;

-- Migration completed successfully
SELECT 'Migration 003 completed: Watch later table created' as status;
