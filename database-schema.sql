-- =====================================================
-- Movie Suggestor - Complete Database Schema
-- =====================================================
-- This is the complete database structure for Movie Suggestor
-- Includes all tables with TMDB integration support
-- =====================================================

-- Create database (if needed)
CREATE DATABASE IF NOT EXISTS moviesuggestor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE moviesuggestor;

-- =====================================================
-- Core Tables
-- =====================================================

-- Migration Tracking Table
CREATE TABLE IF NOT EXISTS migration_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time_ms INT DEFAULT 0,
    status ENUM('success', 'failed', 'rolled_back') DEFAULT 'success',
    error_message TEXT DEFAULT NULL,
    INDEX idx_applied_at (applied_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Migration history tracking';

-- Movies Table (for imported/saved movies - optional with TMDB)
CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    score DECIMAL(3,1) NOT NULL,
    trailer_url VARCHAR(500),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_score (score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Imported movie database';

-- =====================================================
-- User Feature Tables (Phase 2 - TMDB Integration)
-- =====================================================

-- Favorites Table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'User who favorited',
    movie_id INT NOT NULL DEFAULT 0 COMMENT 'Legacy movie_id for backward compatibility',
    tmdb_id INT NOT NULL DEFAULT 0 COMMENT 'TMDB Movie ID (primary identifier)',
    movie_title VARCHAR(255) DEFAULT NULL COMMENT 'Movie title snapshot',
    poster_url VARCHAR(500) DEFAULT NULL COMMENT 'Poster URL snapshot',
    release_year INT DEFAULT NULL COMMENT 'Release year snapshot',
    category VARCHAR(100) DEFAULT NULL COMMENT 'Category snapshot',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When favorited',
    
    -- Indexes
    INDEX idx_user_favorites (user_id, created_at DESC),
    INDEX idx_tmdb_id (tmdb_id),
    INDEX idx_user_tmdb (user_id, tmdb_id),
    
    -- Unique constraint (user can favorite a movie only once)
    UNIQUE KEY unique_user_tmdb (user_id, tmdb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User favorite movies';

-- Watch Later Table
CREATE TABLE IF NOT EXISTS watch_later (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'User ID',
    movie_id INT NOT NULL DEFAULT 0 COMMENT 'Legacy movie_id',
    tmdb_id INT NOT NULL DEFAULT 0 COMMENT 'TMDB Movie ID (primary identifier)',
    movie_title VARCHAR(255) DEFAULT NULL COMMENT 'Movie title snapshot',
    poster_url VARCHAR(500) DEFAULT NULL COMMENT 'Poster URL snapshot',
    release_year INT DEFAULT NULL COMMENT 'Release year snapshot',
    category VARCHAR(100) DEFAULT NULL COMMENT 'Category snapshot',
    watched BOOLEAN DEFAULT FALSE COMMENT 'Watched status',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When added',
    watched_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When watched',
    
    -- Indexes
    INDEX idx_user_unwatched (user_id, watched, added_at DESC),
    INDEX idx_user_watched (user_id, watched, watched_at DESC),
    INDEX idx_tmdb_id (tmdb_id),
    INDEX idx_user_tmdb (user_id, tmdb_id),
    
    -- Unique constraint
    UNIQUE KEY unique_user_tmdb_watch (user_id, tmdb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User watch later list';

-- Ratings Table
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'User who rated',
    movie_id INT NOT NULL DEFAULT 0 COMMENT 'Legacy movie_id',
    tmdb_id INT NOT NULL DEFAULT 0 COMMENT 'TMDB Movie ID (primary identifier)',
    rating DECIMAL(3,1) NOT NULL COMMENT 'User rating (1.0-10.0)',
    review TEXT DEFAULT NULL COMMENT 'Optional review text',
    movie_title VARCHAR(255) DEFAULT NULL COMMENT 'Movie title snapshot',
    poster_url VARCHAR(500) DEFAULT NULL COMMENT 'Poster URL snapshot',
    release_year INT DEFAULT NULL COMMENT 'Release year snapshot',
    category VARCHAR(100) DEFAULT NULL COMMENT 'Category snapshot',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When created',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last updated',
    
    -- Indexes
    INDEX idx_user_ratings (user_id, created_at DESC),
    INDEX idx_movie_ratings (movie_id),
    INDEX idx_tmdb_id (tmdb_id),
    INDEX idx_user_tmdb (user_id, tmdb_id),
    INDEX idx_high_ratings (tmdb_id, rating),
    
    -- Constraints
    UNIQUE KEY unique_user_tmdb_rating (user_id, tmdb_id),
    CONSTRAINT check_rating_range CHECK (rating >= 1 AND rating <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User movie ratings';

-- =====================================================
-- Sample Data (Optional)
-- =====================================================

INSERT IGNORE INTO movies (title, category, score, trailer_url, description) VALUES
('The Shawshank Redemption', 'Δράμα', 9.3, 'https://www.youtube.com/watch?v=6hB3S9bIaco', 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.'),
('The Godfather', 'Αστυνομική', 9.2, 'https://www.youtube.com/watch?v=sY1S34973zA', 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.'),
('The Dark Knight', 'Δράση', 9.0, 'https://www.youtube.com/watch?v=EXeTwQWrcwY', 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests.'),
('Pulp Fiction', 'Αστυνομική', 8.9, 'https://www.youtube.com/watch?v=s7EdQ4FqbhY', 'The lives of two mob hitmen, a boxer, a gangster and his wife intertwine in four tales of violence and redemption.'),
('Forrest Gump', 'Δράμα', 8.8, 'https://www.youtube.com/watch?v=bLvqoHBptjg', 'The presidencies of Kennedy and Johnson, the Vietnam War, and other historical events unfold from the perspective of an Alabama man.'),
('Inception', 'Θρίλερ', 8.8, 'https://www.youtube.com/watch?v=YoHD9XEInc0', 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea.'),
('The Matrix', 'Δράση', 8.7, 'https://www.youtube.com/watch?v=vKQi3bBA1y8', 'A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.'),
('Interstellar', 'Περιπέτεια', 8.6, 'https://www.youtube.com/watch?v=zSWdZVtXT7E', 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity''s survival.');

-- =====================================================
-- Database Setup Complete
-- =====================================================
-- All tables created successfully
-- You can now configure your .env file and start the application
-- =====================================================
