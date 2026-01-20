<?php

namespace MovieSuggestor;

use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Repository for managing movie ratings (TMDB-based)
 * 
 * Stores TMDB movie IDs with snapshot data and user ratings/reviews
 * All methods use prepared statements for security and include proper error handling.
 * 
 * @package MovieSuggestor
 */
class RatingRepositoryTMDB
{
    private PDO $db;

    public function __construct(Database $database)
    {
        $this->db = $database->connect();
    }

    /**
     * Add a new rating for a movie
     * 
     * @param int $userId User ID
     * @param int $tmdbId TMDB Movie ID
     * @param float $rating Rating value (1-10)
     * @param array $movieData Movie snapshot data
     * @param string|null $review Optional review text
     * @return bool True if rating was added successfully
     */
    public function addRating(int $userId, int $tmdbId, float $rating, array $movieData = [], ?string $review = null): bool
    {
        $this->validateUserId($userId);
        $this->validateTmdbId($tmdbId);
        $this->validateRating($rating);

        try {
            $sql = "INSERT INTO ratings 
                    (user_id, tmdb_id, rating, review, movie_title, poster_url, release_year, category, created_at) 
                    VALUES (:user_id, :tmdb_id, :rating, :review, :title, :poster_url, :year, :category, NOW())
                    ON DUPLICATE KEY UPDATE 
                        rating = VALUES(rating),
                        review = VALUES(review),
                        movie_title = VALUES(movie_title),
                        poster_url = VALUES(poster_url),
                        release_year = VALUES(release_year),
                        category = VALUES(category),
                        updated_at = NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':tmdb_id', $tmdbId, PDO::PARAM_INT);
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':review', $review, PDO::PARAM_STR);
            $stmt->bindValue(':title', $movieData['title'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':poster_url', $movieData['poster_url'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':year', $movieData['release_year'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':category', $movieData['category'] ?? null, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding/updating rating: " . $e->getMessage());
            throw new RuntimeException("Failed to add rating");
        }
    }

    /**
     * Update an existing rating
     * 
     * @param int $userId User ID
     * @param int $tmdbId TMDB Movie ID
     * @param float $rating New rating value
     * @param string|null $review Updated review text
     * @return bool True if updated successfully
     */
    public function updateRating(int $userId, int $tmdbId, float $rating, ?string $review = null): bool
    {
        $this->validateUserId($userId);
        $this->validateTmdbId($tmdbId);
        $this->validateRating($rating);

        try {
            $sql = "UPDATE ratings 
                    SET rating = :rating, review = :review, updated_at = NOW()
                    WHERE user_id = :user_id AND tmdb_id = :tmdb_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':tmdb_id', $tmdbId, PDO::PARAM_INT);
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':review', $review, PDO::PARAM_STR);
            
            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error updating rating: " . $e->getMessage());
            throw new RuntimeException("Failed to update rating");
        }
    }

    /**
     * Delete a rating
     * 
     * @param int $userId User ID
     * @param int $tmdbId TMDB Movie ID
     * @return bool True if deleted successfully
     */
    public function deleteRating(int $userId, int $tmdbId): bool
    {
        $this->validateUserId($userId);
        $this->validateTmdbId($tmdbId);

        try {
            $sql = "DELETE FROM ratings WHERE user_id = :user_id AND tmdb_id = :tmdb_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':tmdb_id', $tmdbId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting rating: " . $e->getMessage());
            throw new RuntimeException("Failed to delete rating");
        }
    }

    /**
     * Get user's rating for a specific movie
     * 
     * @param int $userId User ID
     * @param int $tmdbId TMDB Movie ID
     * @return array|null Rating data or null if not found
     */
    public function getUserRating(int $userId, int $tmdbId): ?array
    {
        $this->validateUserId($userId);
        $this->validateTmdbId($tmdbId);

        try {
            $sql = "SELECT rating, review, created_at, updated_at 
                    FROM ratings 
                    WHERE user_id = :user_id AND tmdb_id = :tmdb_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':tmdb_id', $tmdbId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error getting user rating: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve rating");
        }
    }

    /**
     * Get all ratings by a user
     * 
     * @param int $userId User ID
     * @param int $limit Maximum number of results
     * @return array Array of ratings with movie snapshots
     */
    public function getAllUserRatings(int $userId, int $limit = 100): array
    {
        $this->validateUserId($userId);

        try {
            $sql = "SELECT tmdb_id as id, tmdb_id, rating, review, 
                           movie_title as title, poster_url, release_year as year, category,
                           created_at, updated_at
                    FROM ratings
                    WHERE user_id = :user_id
                    ORDER BY updated_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all user ratings: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve ratings");
        }
    }

    /**
     * Get array of TMDB IDs that user has rated (for quick lookups)
     * 
     * @param int $userId User ID
     * @return array Associative array [tmdb_id => rating]
     */
    public function getUserRatedTmdbIds(int $userId): array
    {
        $this->validateUserId($userId);

        try {
            $sql = "SELECT tmdb_id, rating FROM ratings WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $ratings = [];
            foreach ($results as $row) {
                $ratings[$row['tmdb_id']] = (float)$row['rating'];
            }
            return $ratings;
        } catch (PDOException $e) {
            error_log("Error getting rated TMDB IDs: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve rated movie IDs");
        }
    }

    /**
     * Get user's rating statistics
     * 
     * @param int $userId User ID
     * @return array Statistics (count, average, min, max)
     */
    public function getUserRatingStats(int $userId): array
    {
        $this->validateUserId($userId);

        try {
            $sql = "SELECT 
                        COUNT(*) as count,
                        AVG(rating) as average,
                        MIN(rating) as min,
                        MAX(rating) as max
                    FROM ratings
                    WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'count' => (int)($result['count'] ?? 0),
                'average' => round((float)($result['average'] ?? 0), 1),
                'min' => (float)($result['min'] ?? 0),
                'max' => (float)($result['max'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("Error getting rating stats: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve rating statistics");
        }
    }

    private function validateUserId(int $userId): void
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }
    }

    private function validateTmdbId(int $tmdbId): void
    {
        if ($tmdbId <= 0) {
            throw new InvalidArgumentException("TMDB ID must be a positive integer");
        }
    }

    private function validateRating(float $rating): void
    {
        if ($rating < 1.0 || $rating > 10.0) {
            throw new InvalidArgumentException("Rating must be between 1.0 and 10.0");
        }
    }
}
