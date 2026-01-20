<?php

namespace MovieSuggestor;

use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Repository for managing user favorites (TMDB-based)
 * 
 * Stores TMDB movie IDs with snapshot data (title, poster, year, category)
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
     * Add a movie to user's favorites with TMDB ID and snapshot data
     * 
     * @param int $userId User ID
     * @param int $tmdbId TMDB Movie ID
     * @param array $movieData Movie snapshot data (title, poster_url, release_year, category)
     * @return bool True if added successfully
     * @throws InvalidArgumentException If validation fails
     * @throws RuntimeException If database operation fails
     */
    public function addToFavorites(int $userId, int $tmdbId, array $movieData = []): bool
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }
        if ($tmdbId <= 0) {
            throw new InvalidArgumentException("TMDB ID must be a positive integer");
        }

        try {
            $sql = "INSERT INTO favorites 
                    (user_id, tmdb_id, movie_title, poster_url, release_year, category, created_at) 
                    VALUES (:user_id, :tmdb_id, :title, :poster_url, :year, :category, NOW())
                    ON DUPLICATE KEY UPDATE 
                        movie_title = VALUES(movie_title),
                        poster_url = VALUES(poster_url),
                        release_year = VALUES(release_year),
                        category = VALUES(category)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':tmdb_id', $tmdbId, PDO::PARAM_INT);
            $stmt->bindValue(':title', $movieData['title'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':poster_url', $movieData['poster_url'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':year', $movieData['release_year'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':category', $movieData['category'] ?? null, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding favorite: " . $e->getMessage());
            throw new RuntimeException("Failed to add movie to favorites");
        }
    }

    /**
     * Remove a movie from user's favorites by TMDB ID
     * 
     * @param int $userId User ID
     * @param int $tmdbId TMDB Movie ID
     * @return bool True if removed successfully
     * @throws InvalidArgumentException If validation fails
     * @throws RuntimeException If database operation fails
     */
    public function removeFromFavorites(int $userId, int $tmdbId): bool
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }
        if ($tmdbId <= 0) {
            throw new InvalidArgumentException("TMDB ID must be a positive integer");
        }

        try {
            $sql = "DELETE FROM favorites 
                    WHERE user_id = :user_id AND tmdb_id = :tmdb_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':tmdb_id', $tmdbId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error removing favorite: " . $e->getMessage());
            throw new RuntimeException("Failed to remove movie from favorites");
        }
    }

    /**
     * Get all favorite movies for a user (returns snapshot data)
     * 
     * @param int $userId User ID
     * @return array Array of favorite movies with snapshot data
     * @throws InvalidArgumentException If user ID is invalid
     * @throws RuntimeException If database operation fails
     */
    public function getFavorites(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }

        try {
            $sql = "SELECT tmdb_id as id, tmdb_id, movie_title as title, 
                           poster_url, release_year as year, category, 
                           created_at as favorited_at
                    FROM favorites
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC";
            
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
     * Check if a movie is in user's favorites by TMDB ID
     * 
     * @param int $userId User ID
     * @param int $tmdbId TMDB Movie ID
     * @return bool True if the movie is in favorites
     * @throws InvalidArgumentException If validation fails
     * @throws RuntimeException If database operation fails
     */
    public function isFavorite(int $userId, int $tmdbId): bool
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }
        if ($tmdbId <= 0) {
            throw new InvalidArgumentException("TMDB ID must be a positive integer");
        }

        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM favorites 
                    WHERE user_id = :user_id AND tmdb_id = :tmdb_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':tmdb_id', $tmdbId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['count'] ?? 0) > 0;
        } catch (PDOException $e) {
            error_log("Error checking favorite status: " . $e->getMessage());
            throw new RuntimeException("Failed to check favorite status");
        }
    }

    /**
     * Get array of TMDB IDs for user's favorites (for quick lookups)
     * 
     * @param int $userId User ID
     * @return array Array of TMDB IDs
     * @throws InvalidArgumentException If user ID is invalid
     * @throws RuntimeException If database operation fails
     */
    public function getFavoriteTmdbIds(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }

        try {
            $sql = "SELECT tmdb_id FROM favorites WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'tmdb_id');
        } catch (PDOException $e) {
            error_log("Error getting favorite TMDB IDs: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve favorite TMDB IDs");
        }
    }

    /**
     * Get count of favorites for a user
     * 
     * @param int $userId User ID
     * @return int Number of favorites
     * @throws InvalidArgumentException If user ID is invalid
     * @throws RuntimeException If database operation fails
     */
    public function getFavoritesCount(int $userId): int
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }

        try {
            $sql = "SELECT COUNT(*) as count FROM favorites WHERE user_id = :user_id";
            
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
