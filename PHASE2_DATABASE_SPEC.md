# Phase 2 Database Schema Specification

**Version**: 2.0  
**Status**: Draft - Awaiting Phase 1 Approval  
**Date**: January 20, 2026

---

## Overview

This document details all database schema changes required for Phase 2 of the Movie Suggestor application. These changes add enhanced features while maintaining backward compatibility with Phase 1.

---

## Schema Migration Strategy

### Migration Approach
1. **Additive Changes**: All changes are additions (new columns/tables), no deletions
2. **Backward Compatible**: Phase 1 code will continue to work
3. **Default Values**: New columns have sensible defaults
4. **Indexes**: Add indexes after data migration for performance
5. **Transactions**: All migrations wrapped in transactions for safety

### Migration Files
- `migrations/001_add_movie_metadata.sql` - New columns for movies table
- `migrations/002_create_favorites_table.sql` - Favorites system
- `migrations/003_create_watch_later_table.sql` - Watch later system
- `migrations/004_create_ratings_table.sql` - User ratings system
- `migrations/005_create_genres_tables.sql` - Multi-genre support
- `migrations/006_add_indexes.sql` - Performance indexes
- `migrations/007_seed_enhanced_data.sql` - Update sample data

---

## 1. Enhanced Movies Table

### New Columns

```sql
-- Migration 001: Add movie metadata columns
ALTER TABLE movies 
ADD COLUMN release_year INT DEFAULT NULL COMMENT 'Year the movie was released',
ADD COLUMN director VARCHAR(255) DEFAULT NULL COMMENT 'Primary director name',
ADD COLUMN actors TEXT DEFAULT NULL COMMENT 'Comma-separated list of main actors',
ADD COLUMN runtime_minutes INT DEFAULT NULL COMMENT 'Movie runtime in minutes',
ADD COLUMN poster_url VARCHAR(500) DEFAULT NULL COMMENT 'URL to movie poster image',
ADD COLUMN backdrop_url VARCHAR(500) DEFAULT NULL COMMENT 'URL to backdrop/banner image',
ADD COLUMN imdb_rating DECIMAL(3,1) DEFAULT NULL COMMENT 'Official IMDB rating (0.0-10.0)',
ADD COLUMN user_rating DECIMAL(3,1) DEFAULT NULL COMMENT 'Calculated average user rating',
ADD COLUMN votes_count INT DEFAULT 0 COMMENT 'Number of user ratings',
ADD COLUMN view_count INT DEFAULT 0 COMMENT 'Number of times movie was viewed',
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp';
```

### Column Specifications

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| release_year | INT | YES | NULL | Release year (1900-2026) |
| director | VARCHAR(255) | YES | NULL | Director name |
| actors | TEXT | YES | NULL | CSV of actor names |
| runtime_minutes | INT | YES | NULL | Runtime (1-500) |
| poster_url | VARCHAR(500) | YES | NULL | Poster image URL |
| backdrop_url | VARCHAR(500) | YES | NULL | Backdrop image URL |
| imdb_rating | DECIMAL(3,1) | YES | NULL | IMDB score (0.0-10.0) |
| user_rating | DECIMAL(3,1) | YES | NULL | Avg user rating (0.0-10.0) |
| votes_count | INT | NO | 0 | Count of user votes |
| view_count | INT | NO | 0 | View counter |
| updated_at | TIMESTAMP | NO | CURRENT | Last update time |

### Validation Rules

**release_year**:
- Range: 1900 - current year + 2
- Application validates before insert/update

**director**:
- Max length: 255 characters
- Trimmed whitespace

**actors**:
- Stored as comma-separated values
- Application splits for display
- Future: Normalize to separate table

**runtime_minutes**:
- Range: 1 - 500 minutes
- Stored in minutes, displayed as hours:minutes

**poster_url / backdrop_url**:
- Valid URL format
- HTTPS preferred
- Max length: 500 characters

**imdb_rating**:
- Range: 0.0 - 10.0
- One decimal place
- Read-only (not user-editable)

**user_rating**:
- Range: 0.0 - 10.0
- One decimal place
- Calculated automatically from user_ratings table

**votes_count**:
- Auto-incremented when users rate
- Cannot be manually set

---

## 2. Favorites Table

### Purpose
Store user favorite movies using session-based identification.

### Schema

```sql
-- Migration 002: Create favorites table
CREATE TABLE favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique favorite ID',
    session_id VARCHAR(255) NOT NULL COMMENT 'User session identifier',
    movie_id INT NOT NULL COMMENT 'Reference to movies.id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When favorite was added',
    
    -- Foreign key constraint
    CONSTRAINT fk_favorites_movie 
        FOREIGN KEY (movie_id) 
        REFERENCES movies(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Prevent duplicate favorites
    UNIQUE KEY uk_session_movie (session_id, movie_id),
    
    -- Indexes for performance
    INDEX idx_session_id (session_id),
    INDEX idx_movie_id (movie_id),
    INDEX idx_created_at (created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='User favorite movies';
```

### Table Specifications

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT UNSIGNED | NO | PRI | Auto-increment primary key |
| session_id | VARCHAR(255) | NO | MUL | User session ID (cookie-based) |
| movie_id | INT | NO | MUL | Foreign key to movies.id |
| created_at | TIMESTAMP | NO | MUL | Timestamp when added |

### Constraints

**Primary Key**: `id`  
**Foreign Key**: `movie_id` → `movies.id` (CASCADE on delete/update)  
**Unique Constraint**: `(session_id, movie_id)` - One favorite per session per movie  

### Indexes

- `idx_session_id`: Fast lookup of user's favorites
- `idx_movie_id`: Fast lookup of users who favorited a movie
- `idx_created_at`: For sorting by recently added

### Business Rules

1. Each session can favorite a movie only once
2. Deleting a movie removes all favorites for that movie
3. Session expiry (handled in application): 30 days
4. No favorites without valid movie_id

---

## 3. Watch Later Table

### Purpose
Store movies users want to watch later, with watched status tracking.

### Schema

```sql
-- Migration 003: Create watch_later table
CREATE TABLE watch_later (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique watch later ID',
    session_id VARCHAR(255) NOT NULL COMMENT 'User session identifier',
    movie_id INT NOT NULL COMMENT 'Reference to movies.id',
    watched BOOLEAN DEFAULT FALSE COMMENT 'Has user watched this movie',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When added to list',
    watched_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When marked as watched',
    
    -- Foreign key constraint
    CONSTRAINT fk_watch_later_movie 
        FOREIGN KEY (movie_id) 
        REFERENCES movies(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Prevent duplicates
    UNIQUE KEY uk_session_movie_wl (session_id, movie_id),
    
    -- Indexes
    INDEX idx_session_id_wl (session_id),
    INDEX idx_movie_id_wl (movie_id),
    INDEX idx_watched (watched),
    INDEX idx_created_at_wl (created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='User watch later list';
```

### Table Specifications

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT UNSIGNED | NO | PRI | Auto-increment primary key |
| session_id | VARCHAR(255) | NO | MUL | User session ID |
| movie_id | INT | NO | MUL | Foreign key to movies.id |
| watched | BOOLEAN | NO | MUL | Watch status flag |
| created_at | TIMESTAMP | NO | MUL | Added timestamp |
| watched_at | TIMESTAMP | YES | | Watched timestamp |

### Constraints

**Primary Key**: `id`  
**Foreign Key**: `movie_id` → `movies.id` (CASCADE)  
**Unique Constraint**: `(session_id, movie_id)`  

### Indexes

- `idx_session_id_wl`: Fast user queries
- `idx_movie_id_wl`: Fast movie queries
- `idx_watched`: Filter by watched status
- `idx_created_at_wl`: Sort by date added

### Business Rules

1. Movies can be added/removed from watch later
2. Marking as watched sets `watched = TRUE` and `watched_at = NOW()`
3. Unmarking as watched sets `watched = FALSE` and `watched_at = NULL`
4. Deleting movie removes from all watch later lists

---

## 4. User Ratings Table

### Purpose
Store individual user ratings for movies and calculate averages.

### Schema

```sql
-- Migration 004: Create user_ratings table
CREATE TABLE user_ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique rating ID',
    session_id VARCHAR(255) NOT NULL COMMENT 'User session identifier',
    movie_id INT NOT NULL COMMENT 'Reference to movies.id',
    rating DECIMAL(3,1) NOT NULL COMMENT 'User rating (0.0-10.0)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When rating was created',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When rating was last updated',
    
    -- Foreign key constraint
    CONSTRAINT fk_user_ratings_movie 
        FOREIGN KEY (movie_id) 
        REFERENCES movies(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Validation constraint
    CONSTRAINT chk_rating_range 
        CHECK (rating >= 0.0 AND rating <= 10.0),
    
    -- One rating per session per movie
    UNIQUE KEY uk_session_movie_rating (session_id, movie_id),
    
    -- Indexes
    INDEX idx_session_id_rating (session_id),
    INDEX idx_movie_id_rating (movie_id),
    INDEX idx_rating (rating),
    INDEX idx_created_at_rating (created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='User movie ratings';
```

### Table Specifications

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT UNSIGNED | NO | PRI | Auto-increment primary key |
| session_id | VARCHAR(255) | NO | MUL | User session ID |
| movie_id | INT | NO | MUL | Foreign key to movies.id |
| rating | DECIMAL(3,1) | NO | MUL | User's rating (0.0-10.0) |
| created_at | TIMESTAMP | NO | MUL | Original rating time |
| updated_at | TIMESTAMP | NO | | Last update time |

### Constraints

**Primary Key**: `id`  
**Foreign Key**: `movie_id` → `movies.id` (CASCADE)  
**Unique Constraint**: `(session_id, movie_id)` - One rating per session  
**Check Constraint**: `rating BETWEEN 0.0 AND 10.0`  

### Indexes

- `idx_session_id_rating`: User's ratings lookup
- `idx_movie_id_rating`: Movie's ratings lookup (for aggregation)
- `idx_rating`: Filter by rating value
- `idx_created_at_rating`: Sort by date

### Business Rules

1. Ratings must be between 0.0 and 10.0
2. Each session can rate a movie only once
3. Users can update their existing rating
4. New/updated ratings trigger recalculation of movie.user_rating
5. Deleting a movie removes all its ratings

### Trigger for Average Calculation

```sql
-- Trigger to update movie.user_rating after insert/update/delete
DELIMITER $$

CREATE TRIGGER update_movie_rating_after_insert
AFTER INSERT ON user_ratings
FOR EACH ROW
BEGIN
    UPDATE movies 
    SET user_rating = (
        SELECT AVG(rating) 
        FROM user_ratings 
        WHERE movie_id = NEW.movie_id
    ),
    votes_count = (
        SELECT COUNT(*) 
        FROM user_ratings 
        WHERE movie_id = NEW.movie_id
    )
    WHERE id = NEW.movie_id;
END$$

CREATE TRIGGER update_movie_rating_after_update
AFTER UPDATE ON user_ratings
FOR EACH ROW
BEGIN
    UPDATE movies 
    SET user_rating = (
        SELECT AVG(rating) 
        FROM user_ratings 
        WHERE movie_id = NEW.movie_id
    ),
    votes_count = (
        SELECT COUNT(*) 
        FROM user_ratings 
        WHERE movie_id = NEW.movie_id
    )
    WHERE id = NEW.movie_id;
END$$

CREATE TRIGGER update_movie_rating_after_delete
AFTER DELETE ON user_ratings
FOR EACH ROW
BEGIN
    UPDATE movies 
    SET user_rating = (
        SELECT AVG(rating) 
        FROM user_ratings 
        WHERE movie_id = OLD.movie_id
    ),
    votes_count = (
        SELECT COUNT(*) 
        FROM user_ratings 
        WHERE movie_id = OLD.movie_id
    )
    WHERE id = OLD.movie_id;
END$$

DELIMITER ;
```

---

## 5. Genres Tables (Multi-Genre Support)

### Purpose
Allow movies to have multiple genres instead of single category.

### Schema

```sql
-- Migration 005: Create genres tables
CREATE TABLE genres (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Genre ID',
    name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Genre name',
    slug VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL-friendly genre name',
    description TEXT DEFAULT NULL COMMENT 'Genre description',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_slug (slug)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Movie genres';

CREATE TABLE movie_genres (
    movie_id INT NOT NULL COMMENT 'Reference to movies.id',
    genre_id INT UNSIGNED NOT NULL COMMENT 'Reference to genres.id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (movie_id, genre_id),
    
    CONSTRAINT fk_movie_genres_movie 
        FOREIGN KEY (movie_id) 
        REFERENCES movies(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
        
    CONSTRAINT fk_movie_genres_genre 
        FOREIGN KEY (genre_id) 
        REFERENCES genres(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    INDEX idx_movie_id_mg (movie_id),
    INDEX idx_genre_id_mg (genre_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Movie to genre relationships';
```

### Migration Strategy

**Phase 2a**: Keep existing `category` column for backward compatibility  
**Phase 2b**: Populate `genres` and `movie_genres` from existing categories  
**Phase 3**: Eventually deprecate `category` column  

### Sample Genre Data

```sql
INSERT INTO genres (name, slug, description) VALUES
('Action', 'action', 'High-energy films with physical stunts'),
('Drama', 'drama', 'Serious narrative films'),
('Comedy', 'comedy', 'Humorous and light-hearted films'),
('Sci-Fi', 'sci-fi', 'Science fiction and futuristic themes'),
('Romance', 'romance', 'Love and relationship-focused stories'),
('Crime', 'crime', 'Criminal activities and investigations'),
('Animation', 'animation', 'Animated films for all ages'),
('Thriller', 'thriller', 'Suspenseful and tense narratives'),
('Horror', 'horror', 'Scary and frightening content'),
('Documentary', 'documentary', 'Non-fiction factual content');
```

---

## 6. Performance Indexes

### Migration 006: Add Performance Indexes

```sql
-- Add indexes for frequently queried columns
ALTER TABLE movies
ADD INDEX idx_release_year (release_year),
ADD INDEX idx_score (score DESC),
ADD INDEX idx_category (category),
ADD INDEX idx_user_rating (user_rating DESC),
ADD INDEX idx_created_at (created_at DESC),
ADD INDEX idx_composite_filters (category, score, release_year);

-- Composite index for common query patterns
-- Supports: WHERE category=X AND score>=Y AND release_year>=Z
```

### Index Strategy

**Single Column Indexes**:
- `idx_release_year`: Year filtering queries
- `idx_score`: Sorting by IMDB score
- `idx_category`: Category filtering
- `idx_user_rating`: Sorting by user rating

**Composite Index**:
- `idx_composite_filters`: Optimize multi-filter queries
- Order: category, score, release_year (most to least selective)

**Full-Text Index** (Optional for Phase 2b):
```sql
ALTER TABLE movies
ADD FULLTEXT INDEX ft_title_description (title, description);
```

---

## 7. Enhanced Sample Data

### Migration 007: Update Sample Data

```sql
-- Update existing movies with complete metadata
UPDATE movies SET 
    release_year = 1994,
    director = 'Frank Darabont',
    actors = 'Tim Robbins, Morgan Freeman, Bob Gunton',
    runtime_minutes = 142,
    poster_url = 'https://image.tmdb.org/t/p/w500/q6y0Go1tsGEsmtFryDOJo3dEmqu.jpg',
    imdb_rating = 9.3
WHERE title = 'The Shawshank Redemption';

UPDATE movies SET 
    release_year = 1972,
    director = 'Francis Ford Coppola',
    actors = 'Marlon Brando, Al Pacino, James Caan',
    runtime_minutes = 175,
    poster_url = 'https://image.tmdb.org/t/p/w500/3bhkrj58Vtu7enYsRolD1fZdja1.jpg',
    imdb_rating = 9.2
WHERE title = 'The Godfather';

-- ... (Continue for all movies)

-- Populate genres from existing categories
INSERT INTO movie_genres (movie_id, genre_id)
SELECT m.id, g.id
FROM movies m
JOIN genres g ON m.category = g.name;
```

---

## 8. Data Validation Rules

### Application-Level Validation

**Movies Table**:
```php
// release_year
$year >= 1900 && $year <= (date('Y') + 2)

// runtime_minutes
$runtime >= 1 && $runtime <= 500

// URLs
filter_var($url, FILTER_VALIDATE_URL)

// Ratings
$rating >= 0.0 && $rating <= 10.0
```

### Database-Level Validation

**Check Constraints** (MySQL 8.0.16+):
```sql
ALTER TABLE movies
ADD CONSTRAINT chk_release_year 
    CHECK (release_year >= 1900 AND release_year <= 2030),
ADD CONSTRAINT chk_runtime 
    CHECK (runtime_minutes > 0 AND runtime_minutes < 1000),
ADD CONSTRAINT chk_imdb_rating 
    CHECK (imdb_rating >= 0.0 AND imdb_rating <= 10.0),
ADD CONSTRAINT chk_user_rating 
    CHECK (user_rating >= 0.0 AND user_rating <= 10.0);
```

---

## 9. Rollback Plan

Each migration should have a rollback script:

### `rollback/001_remove_movie_metadata.sql`
```sql
ALTER TABLE movies
DROP COLUMN release_year,
DROP COLUMN director,
DROP COLUMN actors,
DROP COLUMN runtime_minutes,
DROP COLUMN poster_url,
DROP COLUMN backdrop_url,
DROP COLUMN imdb_rating,
DROP COLUMN user_rating,
DROP COLUMN votes_count,
DROP COLUMN view_count,
DROP COLUMN updated_at;
```

### `rollback/002_drop_favorites.sql`
```sql
DROP TABLE IF EXISTS favorites;
```

### `rollback/003_drop_watch_later.sql`
```sql
DROP TABLE IF EXISTS watch_later;
```

### `rollback/004_drop_ratings.sql`
```sql
DROP TRIGGER IF EXISTS update_movie_rating_after_insert;
DROP TRIGGER IF EXISTS update_movie_rating_after_update;
DROP TRIGGER IF EXISTS update_movie_rating_after_delete;
DROP TABLE IF EXISTS user_ratings;
```

### `rollback/005_drop_genres.sql`
```sql
DROP TABLE IF EXISTS movie_genres;
DROP TABLE IF EXISTS genres;
```

---

## 10. Migration Execution Order

**Forward Migration**:
1. `001_add_movie_metadata.sql`
2. `002_create_favorites_table.sql`
3. `003_create_watch_later_table.sql`
4. `004_create_ratings_table.sql`
5. `005_create_genres_tables.sql`
6. `006_add_indexes.sql`
7. `007_seed_enhanced_data.sql`

**Rollback** (reverse order):
1. `rollback/005_drop_genres.sql`
2. `rollback/004_drop_ratings.sql`
3. `rollback/003_drop_watch_later.sql`
4. `rollback/002_drop_favorites.sql`
5. `rollback/001_remove_movie_metadata.sql`

---

## 11. Testing Data

### Test Database Setup

```sql
-- Create test database with Phase 2 schema
CREATE DATABASE moviesuggestor_test;
USE moviesuggestor_test;

-- Run all migrations
SOURCE schema.sql;
SOURCE migrations/001_add_movie_metadata.sql;
SOURCE migrations/002_create_favorites_table.sql;
SOURCE migrations/003_create_watch_later_table.sql;
SOURCE migrations/004_create_ratings_table.sql;
SOURCE migrations/005_create_genres_tables.sql;
SOURCE migrations/006_add_indexes.sql;

-- Add test data
INSERT INTO favorites (session_id, movie_id) VALUES
('test_session_1', 1),
('test_session_1', 2),
('test_session_2', 1);

INSERT INTO user_ratings (session_id, movie_id, rating) VALUES
('test_session_1', 1, 9.5),
('test_session_2', 1, 9.0),
('test_session_3', 1, 8.5);
```

---

## Summary

**Total New Tables**: 4 (favorites, watch_later, user_ratings, genres)  
**Total Modified Tables**: 1 (movies - 11 new columns)  
**Total Triggers**: 3 (rating calculations)  
**Total Indexes**: 15+ (for performance)  
**Backward Compatible**: Yes (Phase 1 code works unchanged)  
**Estimated Migration Time**: < 5 minutes on production  
**Data Loss Risk**: None (all additive)  

---

**Next Steps**:
1. ⏳ Wait for Phase 1 approval
2. ⏳ Review this schema specification
3. ⏳ Create actual migration SQL files
4. ⏳ Test on local environment
5. ⏳ Execute migrations
6. ⏳ Verify data integrity
7. ⏳ Begin Phase 2 code development
