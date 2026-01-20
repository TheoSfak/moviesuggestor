<?php

namespace MovieSuggestor;

use PDO;
use PDOException;
use InvalidArgumentException;

/**
 * Repository for managing watch later list
 * 
 * This class provides functionality to:
 * - Add movies to watch later list
 * - Remove movies from watch later list
 * - Mark movies as watched
 * - Retrieve watch later lists (watched and unwatched)
 * - Check if a movie is in the watch later list
 * 
 * @package MovieSuggestor
 */
class WatchLaterRepository
{
    /**
     * @var PDO Database connection
     */
    private PDO $db;

    /**
     * Constructor
     * 
     * @param PDO $db Database connection instance
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Add a movie to watch later list
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @return bool Success status
     * @throws InvalidArgumentException If user ID or movie ID is invalid
     */
    public function addToWatchLater(int $userId, int $movieId): bool
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
        if ($movieId <= 0) {
            throw new InvalidArgumentException('Movie ID must be a positive integer');
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO watch_later (user_id, movie_id, added_at) 
                 VALUES (:user_id, :movie_id, NOW())
                 ON DUPLICATE KEY UPDATE added_at = NOW()'
            );
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding to watch later: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a movie from watch later list
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @return bool Success status
     * @throws InvalidArgumentException If user ID or movie ID is invalid
     */
    public function removeFromWatchLater(int $userId, int $movieId): bool
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
        if ($movieId <= 0) {
            throw new InvalidArgumentException('Movie ID must be a positive integer');
        }

        try {
            $stmt = $this->db->prepare(
                'DELETE FROM watch_later 
                 WHERE user_id = :user_id AND movie_id = :movie_id'
            );
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error removing from watch later: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all movies from watch later list for a user
     * 
     * Returns only unwatched movies by default. Set $includeWatched to true
     * to get all movies including watched ones.
     * 
     * @param int $userId User ID
     * @param bool $includeWatched Whether to include watched movies (default: false)
     * @return array Array of movie objects with watch later metadata
     * @throws InvalidArgumentException If user ID is invalid
     */
    public function getWatchLater(int $userId, bool $includeWatched = false): array
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }

        try {
            $watchedFilter = $includeWatched ? '' : 'AND w.watched = FALSE';
            
            $stmt = $this->db->prepare(
                "SELECT m.*, w.added_at, w.watched, w.watched_at
                 FROM movies m
                 INNER JOIN watch_later w ON m.id = w.movie_id
                 WHERE w.user_id = :user_id {$watchedFilter}
                 ORDER BY w.added_at DESC"
            );
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching watch later list: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a movie is in the watch later list
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @return bool True if movie is in watch later list, false otherwise
     * @throws InvalidArgumentException If user ID or movie ID is invalid
     */
    public function isInWatchLater(int $userId, int $movieId): bool
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
        if ($movieId <= 0) {
            throw new InvalidArgumentException('Movie ID must be a positive integer');
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) as count
                 FROM watch_later
                 WHERE user_id = :user_id AND movie_id = :movie_id'
            );
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error checking watch later status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a movie as watched
     * 
     * Updates the watched status and records the watched timestamp.
     * If the movie is not in the watch later list, it will be added first.
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @return bool Success status
     * @throws InvalidArgumentException If user ID or movie ID is invalid
     */
    public function markAsWatched(int $userId, int $movieId): bool
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
        if ($movieId <= 0) {
            throw new InvalidArgumentException('Movie ID must be a positive integer');
        }

        try {
            // First ensure the movie is in watch later list
            if (!$this->isInWatchLater($userId, $movieId)) {
                $this->addToWatchLater($userId, $movieId);
            }
            
            // Then mark as watched
            $stmt = $this->db->prepare(
                'UPDATE watch_later 
                 SET watched = TRUE, watched_at = NOW()
                 WHERE user_id = :user_id AND movie_id = :movie_id'
            );
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking as watched: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get watched movies history
     * 
     * Returns all movies that have been marked as watched, ordered by watch date.
     * 
     * @param int $userId User ID
     * @param int $limit Maximum number of results to return (default: 100)
     * @return array Array of movie objects with watched_at timestamp
     * @throws InvalidArgumentException If user ID is invalid
     */
    public function getWatchedHistory(int $userId, int $limit = 100): array
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit must be a positive integer');
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT m.*, w.watched_at, w.added_at
                 FROM movies m
                 INNER JOIN watch_later w ON m.id = w.movie_id
                 WHERE w.user_id = :user_id AND w.watched = TRUE
                 ORDER BY w.watched_at DESC
                 LIMIT :limit'
            );
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching watched history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of unwatched movies in watch later list
     * 
     * @param int $userId User ID
     * @return int Number of unwatched movies
     * @throws InvalidArgumentException If user ID is invalid
     */
    public function getUnwatchedCount(int $userId): int
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) as count
                 FROM watch_later
                 WHERE user_id = :user_id AND watched = FALSE'
            );
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
        } catch (PDOException $e) {
            error_log("Error getting unwatched count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear all watched movies from watch later list
     * 
     * Removes all movies that have been marked as watched from the watch later list.
     * 
     * @param int $userId User ID
     * @return bool Success status
     * @throws InvalidArgumentException If user ID is invalid
     */
    public function clearWatched(int $userId): bool
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }

        try {
            $stmt = $this->db->prepare(
                'DELETE FROM watch_later 
                 WHERE user_id = :user_id AND watched = TRUE'
            );
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error clearing watched movies: " . $e->getMessage());
            return false;
        }
    }
}
