<?php

namespace MovieSuggestor;

use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Repository for managing user favorites
 * 
 * Provides functionality to add, remove, and query favorite movies for users.
 * All methods use prepared statements for security and include proper error handling.
 * 
 * @package MovieSuggestor
 */
class FavoritesRepository
{
    private PDO $db;

    /**
     * Constructor
     * 
     * @param Database $database Database instance
     */
    public function __construct(Database $database)
    {
        $this->db = $database->connect();
    }

    /**
     * Add a movie to user's favorites
     * 
     * Prevents duplicate entries by using INSERT IGNORE.
     * Validates that both user ID and movie ID are positive integers.
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @return bool True if added successfully or already exists, false on failure
     * @throws InvalidArgumentException If user ID or movie ID is invalid
     */
    public function addToFavorites(int $userId, int $movieId): bool
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }
        if ($movieId <= 0) {
            throw new InvalidArgumentException("Movie ID must be a positive integer");
        }

        try {
            $sql = "INSERT IGNORE INTO favorites (user_id, movie_id, created_at) 
                    VALUES (:user_id, :movie_id, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding favorite: " . $e->getMessage());
            throw new RuntimeException("Failed to add movie to favorites");
        }
    }

    /**
     * Remove a movie from user's favorites
     * 
     * Validates that both user ID and movie ID are positive integers.
     * Returns true even if the favorite didn't exist.
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @return bool True if removed successfully or didn't exist, false on failure
     * @throws InvalidArgumentException If user ID or movie ID is invalid
     */
    public function removeFromFavorites(int $userId, int $movieId): bool
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }
        if ($movieId <= 0) {
            throw new InvalidArgumentException("Movie ID must be a positive integer");
        }

        try {
            $sql = "DELETE FROM favorites 
                    WHERE user_id = :user_id AND movie_id = :movie_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error removing favorite: " . $e->getMessage());
            throw new RuntimeException("Failed to remove movie from favorites");
        }
    }

    /**
     * Get all favorite movies for a user
     * 
     * Returns movies with all their metadata, ordered by when they were favorited (newest first).
     * 
     * @param int $userId User ID
     * @return array Array of movie objects with all fields
     * @throws InvalidArgumentException If user ID is invalid
     */
    public function getFavorites(int $userId): array
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }

        try {
            $sql = "SELECT m.id, m.title, m.category, m.score, m.trailer_url, 
                           m.description, m.release_year, m.runtime_minutes, m.director, 
                           m.actors, m.poster_url, m.backdrop_url, m.imdb_rating, 
                           m.user_rating, m.votes_count, m.created_at, m.updated_at,
                           f.created_at as favorited_at
                    FROM movies m
                    INNER JOIN favorites f ON m.id = f.movie_id
                    WHERE f.user_id = :user_id
                    ORDER BY f.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting favorites: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve favorites");
        }
    }

    /**
     * Check if a movie is in user's favorites
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @return bool True if the movie is in user's favorites, false otherwise
     * @throws InvalidArgumentException If user ID or movie ID is invalid
     */
    public function isFavorite(int $userId, int $movieId): bool
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }
        if ($movieId <= 0) {
            throw new InvalidArgumentException("Movie ID must be a positive integer");
        }

        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM favorites 
                    WHERE user_id = :user_id AND movie_id = :movie_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['count'] ?? 0) > 0;
        } catch (PDOException $e) {
            error_log("Error checking favorite status: " . $e->getMessage());
            throw new RuntimeException("Failed to check favorite status");
        }
    }

    /**
     * Get count of favorites for a user
     * 
     * @param int $userId User ID
     * @return int Number of favorites for the user
     * @throws InvalidArgumentException If user ID is invalid
     */
    public function getFavoritesCount(int $userId): int
    {
        // Validate input
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }

        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM favorites 
                    WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error getting favorites count: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve favorites count");
        }
    }
}
