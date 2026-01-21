<?php
/**
 * RatingRepository Test Suite
 * 
 * Comprehensive tests for the RatingRepository class including:
 * - Adding, updating, and deleting ratings
 * - Retrieving user ratings and movie ratings
 * - Calculating average ratings
 * - Input validation (rating range, IDs)
 * - Error handling
 * - SQL injection prevention
 * - Review text handling
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/RatingRepository.php';

use PHPUnit\Framework\TestCase;
use MovieSuggestor\Database;
use MovieSuggestor\RatingRepository;

/**
 * Class RatingRepositoryTest
 * 
 * Tests all RatingRepository functionality including CRUD operations,
 * average calculations, validation, and error handling.
 */
class RatingRepositoryTest extends TestCase
{
    private Database $database;
    private RatingRepository $repository;
    private PDO $pdo;

    /**
     * Set up test fixture before each test
     */
    protected function setUp(): void
    {
        // Use test database
        putenv('DB_NAME=moviesuggestor_test');
        
        $this->database = new Database();
        $this->pdo = $this->database->connect();
        $this->repository = new RatingRepository($this->database);
        
        // Clean up test data
        $this->cleanDatabase();
        
        // Insert test movies
        $this->insertTestMovies();
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        $this->cleanDatabase();
    }

    /**
     * Clean test database
     */
    private function cleanDatabase(): void
    {
        $this->pdo->exec('DELETE FROM ratings WHERE user_id IN (999, 998, 997, 996)');
        $this->pdo->exec('DELETE FROM movies WHERE id IN (9990, 9991, 9992, 9993)');
    }

    /**
     * Insert test movies for testing
     */
    private function insertTestMovies(): void
    {
        $sql = "INSERT INTO movies (id, title, category, score, trailer_url) VALUES 
                (9990, 'Rating Test Movie 1', 'Action', 8.5, 'http://example.com/trailer1'),
                (9991, 'Rating Test Movie 2', 'Drama', 7.5, 'http://example.com/trailer2'),
                (9992, 'Rating Test Movie 3', 'Sci-Fi', 9.0, 'http://example.com/trailer3'),
                (9993, 'Rating Test Movie 4', 'Comedy', 6.5, 'http://example.com/trailer4')
                ON DUPLICATE KEY UPDATE title=VALUES(title)";
        $this->pdo->exec($sql);
    }

    // =================================================================
    // ADD RATING TESTS
    // =================================================================

    /**
     * Test adding a rating successfully
     */
    public function testAddRatingSuccess(): void
    {
        $result = $this->repository->addRating(999, 9990, 8.5);
        
        $this->assertTrue($result);
        
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals(8.5, $rating['rating']);
    }

    /**
     * Test adding a rating with review
     */
    public function testAddRatingWithReview(): void
    {
        $result = $this->repository->addRating(999, 9990, 9.0, [], 'Excellent movie!');
        
        $this->assertTrue($result);
        
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals(9.0, $rating['rating']);
        $this->assertEquals('Excellent movie!', $rating['review']);
    }

    /**
     * Test adding minimum rating (1)
     */
    public function testAddRatingMinimum(): void
    {
        $result = $this->repository->addRating(999, 9990, 1.0);
        
        $this->assertTrue($result);
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals(1.0, $rating['rating']);
    }

    /**
     * Test adding maximum rating (10)
     */
    public function testAddRatingMaximum(): void
    {
        $result = $this->repository->addRating(999, 9990, 10.0);
        
        $this->assertTrue($result);
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals(10.0, $rating['rating']);
    }

    /**
     * Test adding rating with decimal precision
     */
    public function testAddRatingWithDecimals(): void
    {
        $result = $this->repository->addRating(999, 9990, 7.8);
        
        $this->assertTrue($result);
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals(7.8, $rating['rating']);
    }

    /**
     * Test adding duplicate rating throws exception
     */
    public function testAddDuplicateRatingThrows(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User has already rated this movie');
        
        $this->repository->addRating(999, 9990, 9.0);
    }

    /**
     * Test adding with invalid user ID throws exception
     */
    public function testAddRatingInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->addRating(0, 9990, 8.0);
    }

    /**
     * Test adding with negative user ID throws exception
     */
    public function testAddRatingNegativeUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->addRating(-1, 9990, 8.0);
    }

    /**
     * Test adding with invalid movie ID throws exception
     */
    public function testAddRatingInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->addRating(999, 0, 8.0);
    }

    /**
     * Test adding with rating below minimum throws exception
     */
    public function testAddRatingBelowMinimumThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 10');
        
        $this->repository->addRating(999, 9990, 0.5);
    }

    /**
     * Test adding with rating above maximum throws exception
     */
    public function testAddRatingAboveMaximumThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 10');
        
        $this->repository->addRating(999, 9990, 10.5);
    }

    /**
     * Test adding with negative rating throws exception
     */
    public function testAddRatingNegativeRatingThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 10');
        
        $this->repository->addRating(999, 9990, -5.0);
    }

    // =================================================================
    // UPDATE RATING TESTS
    // =================================================================

    /**
     * Test updating a rating successfully
     */
    public function testUpdateRatingSuccess(): void
    {
        $this->repository->addRating(999, 9990, 7.0);
        $result = $this->repository->updateRating(999, 9990, 9.0);
        
        $this->assertTrue($result);
        
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals(9.0, $rating['rating']);
    }

    /**
     * Test updating rating with new review
     */
    public function testUpdateRatingWithReview(): void
    {
        $this->repository->addRating(999, 9990, 7.0, [], 'Good movie');
        $result = $this->repository->updateRating(999, 9990, 9.0, 'Great movie!');
        
        $this->assertTrue($result);
        
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals(9.0, $rating['rating']);
        $this->assertEquals('Great movie!', $rating['review']);
    }

    /**
     * Test updating rating clears review when null
     */
    public function testUpdateRatingClearsReview(): void
    {
        $this->repository->addRating(999, 9990, 7.0, [], 'Good movie');
        $result = $this->repository->updateRating(999, 9990, 8.0, null);
        
        $this->assertTrue($result);
        
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertNull($rating['review']);
    }

    /**
     * Test updating non-existent rating throws exception
     */
    public function testUpdateNonExistentRatingThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rating not found');
        
        $this->repository->updateRating(999, 9990, 8.0);
    }

    /**
     * Test updating with invalid rating throws exception
     */
    public function testUpdateRatingInvalidRatingThrows(): void
    {
        $this->repository->addRating(999, 9990, 7.0);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 10');
        
        $this->repository->updateRating(999, 9990, 15.0);
    }

    /**
     * Test updating with invalid user ID throws exception
     */
    public function testUpdateRatingInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->updateRating(0, 9990, 8.0);
    }

    /**
     * Test updating with invalid movie ID throws exception
     */
    public function testUpdateRatingInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->updateRating(999, -1, 8.0);
    }

    // =================================================================
    // DELETE RATING TESTS
    // =================================================================

    /**
     * Test deleting a rating successfully
     */
    public function testDeleteRatingSuccess(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        $result = $this->repository->deleteRating(999, 9990);
        
        $this->assertTrue($result);
        
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertNull($rating);
    }

    /**
     * Test deleting non-existent rating throws exception
     */
    public function testDeleteNonExistentRatingThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rating not found');
        
        $this->repository->deleteRating(999, 9990);
    }

    /**
     * Test deleting with invalid user ID throws exception
     */
    public function testDeleteRatingInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->deleteRating(0, 9990);
    }

    /**
     * Test deleting with invalid movie ID throws exception
     */
    public function testDeleteRatingInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->deleteRating(999, 0);
    }

    // =================================================================
    // GET USER RATING TESTS
    // =================================================================

    /**
     * Test getting user rating returns correct data
     */
    public function testGetUserRatingReturnsCorrectData(): void
    {
        $this->repository->addRating(999, 9990, 8.5, [], 'Great movie!');
        $rating = $this->repository->getUserRating(999, 9990);
        
        $this->assertNotNull($rating);
        $this->assertEquals(999, $rating['user_id']);
        $this->assertEquals(9990, $rating['movie_id']);
        $this->assertEquals(8.5, $rating['rating']);
        $this->assertEquals('Great movie!', $rating['review']);
        $this->assertArrayHasKey('created_at', $rating);
        $this->assertArrayHasKey('updated_at', $rating);
    }

    /**
     * Test getting non-existent rating returns null
     */
    public function testGetUserRatingNonExistentReturnsNull(): void
    {
        $rating = $this->repository->getUserRating(999, 9990);
        
        $this->assertNull($rating);
    }

    /**
     * Test getting rating is user-specific
     */
    public function testGetUserRatingUserSpecific(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        $this->repository->addRating(998, 9990, 6.0);
        
        $rating999 = $this->repository->getUserRating(999, 9990);
        $rating998 = $this->repository->getUserRating(998, 9990);
        
        $this->assertEquals(8.0, $rating999['rating']);
        $this->assertEquals(6.0, $rating998['rating']);
    }

    /**
     * Test getting user rating with invalid user ID throws exception
     */
    public function testGetUserRatingInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->getUserRating(-1, 9990);
    }

    /**
     * Test getting user rating with invalid movie ID throws exception
     */
    public function testGetUserRatingInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->getUserRating(999, 0);
    }

    // =================================================================
    // AVERAGE RATING TESTS
    // =================================================================

    /**
     * Test getting average rating with no ratings returns null
     */
    public function testGetAverageRatingNoRatingsReturnsNull(): void
    {
        $average = $this->repository->getAverageRating(9990);
        
        $this->assertNull($average);
    }

    /**
     * Test getting average rating with single rating
     */
    public function testGetAverageRatingSingleRating(): void
    {
        $this->repository->addRating(999, 9990, 8.5);
        $average = $this->repository->getAverageRating(9990);
        
        $this->assertEquals(8.5, $average);
    }

    /**
     * Test getting average rating with multiple ratings
     */
    public function testGetAverageRatingMultipleRatings(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        $this->repository->addRating(998, 9990, 9.0);
        $this->repository->addRating(997, 9990, 7.0);
        
        $average = $this->repository->getAverageRating(9990);
        
        $this->assertEquals(8.0, $average); // (8 + 9 + 7) / 3 = 8.0
    }

    /**
     * Test average rating is rounded to 1 decimal place
     */
    public function testGetAverageRatingRounded(): void
    {
        $this->repository->addRating(999, 9990, 8.3);
        $this->repository->addRating(998, 9990, 7.6);
        $this->repository->addRating(997, 9990, 9.2);
        
        $average = $this->repository->getAverageRating(9990);
        
        // (8.3 + 7.6 + 9.2) / 3 = 8.366... rounded to 8.4
        $this->assertEquals(8.4, $average);
    }

    /**
     * Test getting average rating with invalid movie ID throws exception
     */
    public function testGetAverageRatingInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->getAverageRating(0);
    }

    // =================================================================
    // RATINGS COUNT TESTS
    // =================================================================

    /**
     * Test getting ratings count for movie with no ratings
     */
    public function testGetRatingsCountZero(): void
    {
        $count = $this->repository->getRatingsCount(9990);
        
        $this->assertEquals(0, $count);
    }

    /**
     * Test getting ratings count after adding ratings
     */
    public function testGetRatingsCountAfterAdding(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        $this->repository->addRating(998, 9990, 7.0);
        $this->repository->addRating(997, 9990, 9.0);
        
        $count = $this->repository->getRatingsCount(9990);
        
        $this->assertEquals(3, $count);
    }

    /**
     * Test getting ratings count after deleting rating
     */
    public function testGetRatingsCountAfterDeleting(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        $this->repository->addRating(998, 9990, 7.0);
        $this->repository->deleteRating(999, 9990);
        
        $count = $this->repository->getRatingsCount(9990);
        
        $this->assertEquals(1, $count);
    }

    /**
     * Test getting ratings count with invalid movie ID throws exception
     */
    public function testGetRatingsCountInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->getRatingsCount(-1);
    }

    // =================================================================
    // GET ALL RATINGS TESTS
    // =================================================================

    /**
     * Test getting all ratings for movie with no ratings
     */
    public function testGetAllRatingsEmpty(): void
    {
        $ratings = $this->repository->getAllRatings(9990);
        
        $this->assertIsArray($ratings);
        $this->assertEmpty($ratings);
    }

    /**
     * Test getting all ratings returns correct structure
     */
    public function testGetAllRatingsReturnsCorrectStructure(): void
    {
        $this->repository->addRating(999, 9990, 8.0, [], 'Good movie');
        $ratings = $this->repository->getAllRatings(9990);
        
        $this->assertCount(1, $ratings);
        $this->assertArrayHasKey('id', $ratings[0]);
        $this->assertArrayHasKey('user_id', $ratings[0]);
        $this->assertArrayHasKey('movie_id', $ratings[0]);
        $this->assertArrayHasKey('rating', $ratings[0]);
        $this->assertArrayHasKey('review', $ratings[0]);
        $this->assertArrayHasKey('created_at', $ratings[0]);
        $this->assertArrayHasKey('updated_at', $ratings[0]);
    }

    /**
     * Test getting all ratings ordered by newest first
     */
    public function testGetAllRatingsOrderedByNewest(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        sleep(1);
        $this->repository->addRating(998, 9990, 7.0);
        
        $ratings = $this->repository->getAllRatings(9990);
        
        $this->assertEquals(998, $ratings[0]['user_id']);
        $this->assertEquals(999, $ratings[1]['user_id']);
    }

    /**
     * Test getting all ratings with limit
     */
    public function testGetAllRatingsWithLimit(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        $this->repository->addRating(998, 9990, 7.0);
        $this->repository->addRating(997, 9990, 9.0);
        
        $ratings = $this->repository->getAllRatings(9990, 2);
        
        $this->assertCount(2, $ratings);
    }

    /**
     * Test getting all ratings with offset
     */
    public function testGetAllRatingsWithOffset(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        $this->repository->addRating(998, 9990, 7.0);
        $this->repository->addRating(997, 9990, 9.0);
        
        $ratings = $this->repository->getAllRatings(9990, 10, 1);
        
        $this->assertCount(2, $ratings);
    }

    /**
     * Test getting all ratings with invalid limit throws exception
     */
    public function testGetAllRatingsInvalidLimitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be between 1 and 1000');
        
        $this->repository->getAllRatings(9990, 0);
    }

    /**
     * Test getting all ratings with limit too large throws exception
     */
    public function testGetAllRatingsLimitTooLargeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be between 1 and 1000');
        
        $this->repository->getAllRatings(9990, 1001);
    }

    /**
     * Test getting all ratings with negative offset throws exception
     */
    public function testGetAllRatingsNegativeOffsetThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset must be non-negative');
        
        $this->repository->getAllRatings(9990, 10, -1);
    }

    /**
     * Test getting all ratings with invalid movie ID throws exception
     */
    public function testGetAllRatingsInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->getAllRatings(0);
    }

    // =================================================================
    // EDGE CASES AND ERROR HANDLING
    // =================================================================

    /**
     * Test that data persists across repository instances
     */
    public function testDataPersistsAcrossInstances(): void
    {
        $this->repository->addRating(999, 9990, 8.5);
        
        $newRepository = new RatingRepository($this->database);
        $rating = $newRepository->getUserRating(999, 9990);
        
        $this->assertEquals(8.5, $rating['rating']);
    }

    /**
     * Test handling of large user IDs
     */
    public function testLargeUserIds(): void
    {
        $largeUserId = 2147483647; // Max int32
        
        $result = $this->repository->addRating($largeUserId, 9990, 8.0);
        $this->assertTrue($result);
        
        // Cleanup
        $this->pdo->exec("DELETE FROM ratings WHERE user_id = $largeUserId");
    }

    /**
     * Test handling of very long review text
     */
    public function testLongReviewText(): void
    {
        $longReview = str_repeat('This is a great movie! ', 100); // ~2300 chars
        
        $result = $this->repository->addRating(999, 9990, 9.0, [], $longReview);
        $this->assertTrue($result);
        
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals($longReview, $rating['review']);
    }

    /**
     * Test special characters in review
     */
    public function testSpecialCharactersInReview(): void
    {
        $review = "It's a \"great\" movie! <3 & more...";
        
        $result = $this->repository->addRating(999, 9990, 9.0, [], $review);
        $this->assertTrue($result);
        
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals($review, $rating['review']);
    }

    /**
     * Test SQL injection prevention in review
     */
    public function testSQLInjectionPreventionInReview(): void
    {
        $maliciousReview = "'; DROP TABLE ratings; --";
        
        $result = $this->repository->addRating(999, 9990, 8.0, [], $maliciousReview);
        $this->assertTrue($result);
        
        // Verify rating was added safely
        $rating = $this->repository->getUserRating(999, 9990);
        $this->assertEquals($maliciousReview, $rating['review']);
        
        // Verify table still exists
        $count = $this->repository->getRatingsCount(9990);
        $this->assertEquals(1, $count);
    }

    /**
     * Test timestamp updates on rating update
     */
    public function testTimestampUpdatesOnUpdate(): void
    {
        $this->repository->addRating(999, 9990, 7.0);
        $original = $this->repository->getUserRating(999, 9990);
        
        sleep(1);
        
        $this->repository->updateRating(999, 9990, 9.0);
        $updated = $this->repository->getUserRating(999, 9990);
        
        $this->assertNotEquals($original['updated_at'], $updated['updated_at']);
    }

    /**
     * Test multiple users can rate same movie
     */
    public function testMultipleUsersRateSameMovie(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        $this->repository->addRating(998, 9990, 7.0);
        $this->repository->addRating(997, 9990, 9.0);
        
        $this->assertEquals(3, $this->repository->getRatingsCount(9990));
        $this->assertEquals(8.0, $this->repository->getAverageRating(9990));
    }

    /**
     * Test single user can rate multiple movies
     */
    public function testSingleUserRatesMultipleMovies(): void
    {
        $this->repository->addRating(999, 9990, 8.0);
        $this->repository->addRating(999, 9991, 7.0);
        $this->repository->addRating(999, 9992, 9.0);
        
        $this->assertNotNull($this->repository->getUserRating(999, 9990));
        $this->assertNotNull($this->repository->getUserRating(999, 9991));
        $this->assertNotNull($this->repository->getUserRating(999, 9992));
    }

    /**
     * Test rating with exact boundary values
     */
    public function testRatingBoundaryValues(): void
    {
        // Test exactly 1
        $result1 = $this->repository->addRating(999, 9990, 1.0);
        $this->assertTrue($result1);
        
        // Test exactly 10
        $result2 = $this->repository->addRating(998, 9990, 10.0);
        $this->assertTrue($result2);
        
        // Test just below 1
        $this->expectException(InvalidArgumentException::class);
        $this->repository->addRating(997, 9990, 0.9999);
    }

    /**
     * Test deleting rating updates average
     */
    public function testDeletingRatingUpdatesAverage(): void
    {
        $this->repository->addRating(999, 9990, 10.0);
        $this->repository->addRating(998, 9990, 8.0);
        
        $this->assertEquals(9.0, $this->repository->getAverageRating(9990));
        
        $this->repository->deleteRating(999, 9990);
        
        $this->assertEquals(8.0, $this->repository->getAverageRating(9990));
    }

    /**
     * Test SQL injection prevention in user ID
     */
    public function testSQLInjectionPreventionUserId(): void
    {
        $this->expectException(TypeError::class);
        
        // @phpstan-ignore-next-line - Testing type safety
        $this->repository->addRating("999' OR '1'='1", 9990, 8.0);
    }

    /**
     * Test SQL injection prevention in movie ID
     */
    public function testSQLInjectionPreventionMovieId(): void
    {
        $this->expectException(TypeError::class);
        
        // @phpstan-ignore-next-line - Testing type safety
        $this->repository->addRating(999, "9990'; DROP TABLE ratings; --", 8.0);
    }
}
