<?php

namespace MovieSuggestor;

use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Repository for managing movie ratings
 * 
 * Provides functionality to add, update, delete, and query movie ratings.
 * All methods use prepared statements for security and include proper error handling.
 * Ratings must be between 1 and 10 (inclusive).
 * 
 * @package MovieSuggestor
 */
class RatingRepository
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
     * Add a new rating for a movie
     * 
     * Creates a new rating record. If the user has already rated this movie,
     * use updateRating() instead or this will throw an exception due to unique constraint.
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @param float $rating Rating value (1-10)
     * @param string|null $review Optional review text
     * @return bool True if rating was added successfully
     * @throws InvalidArgumentException If validation fails
     * @throws RuntimeException If database operation fails
     */
    public function addRating(int $userId, int $movieId, float $rating, ?string $review = null): bool
    {
        // Validate input
        $this->validateUserId($userId);
        $this->validateMovieId($movieId);
        $this->validateRating($rating);

        try {
            $sql = "INSERT INTO ratings (user_id, movie_id, rating, review, created_at) 
                    VALUES (:user_id, :movie_id, :rating, :review, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':review', $review, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding rating: " . $e->getMessage());
            
            // Check for duplicate entry error
            if ($e->getCode() == 23000) {
                throw new RuntimeException("User has already rated this movie. Use updateRating() instead.");
            }
            
            throw new RuntimeException("Failed to add rating");
        }
    }

    /**
     * Update an existing rating for a movie
     * 
     * Updates both the rating value and optional review text.
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @param float $rating New rating value (1-10)
     * @param string|null $review Optional review text (null to clear existing review)
     * @return bool True if rating was updated successfully
     * @throws InvalidArgumentException If validation fails
     * @throws RuntimeException If database operation fails
     */
    public function updateRating(int $userId, int $movieId, float $rating, ?string $review = null): bool
    {
        // Validate input
        $this->validateUserId($userId);
        $this->validateMovieId($movieId);
        $this->validateRating($rating);

        try {
            $sql = "UPDATE ratings 
                    SET rating = :rating, 
                        review = :review, 
                        updated_at = NOW() 
                    WHERE user_id = :user_id AND movie_id = :movie_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':review', $review, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            // Check if any rows were affected
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException("Rating not found for this user and movie");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error updating rating: " . $e->getMessage());
            throw new RuntimeException("Failed to update rating");
        }
    }

    /**
     * Delete a rating
     * 
     * Removes a user's rating for a specific movie.
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @return bool True if rating was deleted successfully
     * @throws InvalidArgumentException If validation fails
     * @throws RuntimeException If database operation fails
     */
    public function deleteRating(int $userId, int $movieId): bool
    {
        // Validate input
        $this->validateUserId($userId);
        $this->validateMovieId($movieId);

        try {
            $sql = "DELETE FROM ratings 
                    WHERE user_id = :user_id AND movie_id = :movie_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            // Check if any rows were affected
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException("Rating not found for this user and movie");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error deleting rating: " . $e->getMessage());
            throw new RuntimeException("Failed to delete rating");
        }
    }

    /**
     * Get a user's rating for a specific movie
     * 
     * Returns the complete rating record including review and timestamps.
     * 
     * @param int $userId User ID
     * @param int $movieId Movie ID
     * @return array|null Rating data or null if not found
     * @throws InvalidArgumentException If validation fails
     */
    public function getUserRating(int $userId, int $movieId): ?array
    {
        // Validate input
        $this->validateUserId($userId);
        $this->validateMovieId($movieId);

        try {
            $sql = "SELECT id, user_id, movie_id, rating, review, created_at, updated_at 
                    FROM ratings 
                    WHERE user_id = :user_id AND movie_id = :movie_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching user rating: " . $e->getMessage());
            throw new RuntimeException("Failed to fetch user rating");
        }
    }

    /**
     * Get the average rating for a movie
     * 
     * Calculates the average of all ratings for a specific movie.
     * Returns null if the movie has no ratings.
     * 
     * @param int $movieId Movie ID
     * @return float|null Average rating (rounded to 1 decimal place) or null if no ratings
     * @throws InvalidArgumentException If movie ID is invalid
     */
    public function getAverageRating(int $movieId): ?float
    {
        // Validate input
        $this->validateMovieId($movieId);

        try {
            $sql = "SELECT AVG(rating) as average 
                    FROM ratings 
                    WHERE movie_id = :movie_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['average'] !== null) {
                return round((float)$result['average'], 1);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Error calculating average rating: " . $e->getMessage());
            throw new RuntimeException("Failed to calculate average rating");
        }
    }

    /**
     * Get the total count of ratings for a movie
     * 
     * Returns the number of users who have rated this movie.
     * 
     * @param int $movieId Movie ID
     * @return int Number of ratings
     * @throws InvalidArgumentException If movie ID is invalid
     */
    public function getRatingsCount(int $movieId): int
    {
        // Validate input
        $this->validateMovieId($movieId);

        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM ratings 
                    WHERE movie_id = :movie_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error counting ratings: " . $e->getMessage());
            throw new RuntimeException("Failed to count ratings");
        }
    }

    /**
     * Get all ratings for a movie
     * 
     * Returns all rating records for a specific movie, ordered by creation date (newest first).
     * Includes user ID, rating value, review, and timestamps.
     * 
     * @param int $movieId Movie ID
     * @param int $limit Maximum number of ratings to return (default: 100)
     * @param int $offset Offset for pagination (default: 0)
     * @return array Array of rating records
     * @throws InvalidArgumentException If validation fails
     */
    public function getAllRatings(int $movieId, int $limit = 100, int $offset = 0): array
    {
        // Validate input
        $this->validateMovieId($movieId);
        
        if ($limit <= 0 || $limit > 1000) {
            throw new InvalidArgumentException("Limit must be between 1 and 1000");
        }
        
        if ($offset < 0) {
            throw new InvalidArgumentException("Offset must be non-negative");
        }

        try {
            $sql = "SELECT id, user_id, movie_id, rating, review, created_at, updated_at 
                    FROM ratings 
                    WHERE movie_id = :movie_id 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all ratings: " . $e->getMessage());
            throw new RuntimeException("Failed to fetch ratings");
        }
    }

    /**
     * Validate user ID
     * 
     * @param int $userId User ID to validate
     * @throws InvalidArgumentException If user ID is invalid
     */
    private function validateUserId(int $userId): void
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer");
        }
    }

    /**
     * Validate movie ID
     * 
     * @param int $movieId Movie ID to validate
     * @throws InvalidArgumentException If movie ID is invalid
     */
    private function validateMovieId(int $movieId): void
    {
        if ($movieId <= 0) {
            throw new InvalidArgumentException("Movie ID must be a positive integer");
        }
    }

    /**
     * Validate rating value
     * 
     * Ensures rating is between 1 and 10 (inclusive).
     * 
     * @param float $rating Rating value to validate
     * @throws InvalidArgumentException If rating is invalid
     */
    private function validateRating(float $rating): void
    {
        if ($rating < 1 || $rating > 10) {
            throw new InvalidArgumentException("Rating must be between 1 and 10");
        }
    }
}
