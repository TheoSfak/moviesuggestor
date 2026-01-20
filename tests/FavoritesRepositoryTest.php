<?php
/**
 * FavoritesRepository Test Suite
 * 
 * Comprehensive tests for the FavoritesRepository class including:
 * - Adding and removing favorites
 * - Retrieving favorites lists
 * - Checking favorite status
 * - Input validation
 * - Error handling
 * - SQL injection prevention
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/FavoritesRepository.php';

use PHPUnit\Framework\TestCase;
use MovieSuggestor\Database;
use MovieSuggestor\FavoritesRepository;

/**
 * Class FavoritesRepositoryTest
 * 
 * Tests all FavoritesRepository functionality including CRUD operations,
 * validation, and error handling.
 */
class FavoritesRepositoryTest extends TestCase
{
    private Database $database;
    private FavoritesRepository $repository;
    private PDO $pdo;

    /**
     * Set up test fixture before each test
     * 
     * Creates test database connection and clears test data
     */
    protected function setUp(): void
    {
        // Use test database
        putenv('DB_NAME=moviesuggestor_test');
        
        $this->database = new Database();
        $this->pdo = $this->database->connect();
        $this->repository = new FavoritesRepository($this->database);
        
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
        $this->pdo->exec('DELETE FROM favorites WHERE user_id IN (999, 998, 997)');
        $this->pdo->exec('DELETE FROM movies WHERE id IN (9990, 9991, 9992)');
    }

    /**
     * Insert test movies for testing
     */
    private function insertTestMovies(): void
    {
        $sql = "INSERT INTO movies (id, title, category, score, trailer_url) VALUES 
                (9990, 'Test Movie 1', 'Action', 8.5, 'http://example.com/trailer1'),
                (9991, 'Test Movie 2', 'Drama', 7.5, 'http://example.com/trailer2'),
                (9992, 'Test Movie 3', 'Sci-Fi', 9.0, 'http://example.com/trailer3')
                ON DUPLICATE KEY UPDATE title=VALUES(title)";
        $this->pdo->exec($sql);
    }

    // =================================================================
    // ADD TO FAVORITES TESTS
    // =================================================================

    /**
     * Test adding a movie to favorites successfully
     */
    public function testAddToFavoritesSuccess(): void
    {
        $result = $this->repository->addToFavorites(999, 9990);
        
        $this->assertTrue($result);
        $this->assertTrue($this->repository->isFavorite(999, 9990));
    }

    /**
     * Test adding same movie twice uses INSERT IGNORE
     */
    public function testAddToFavoritesTwiceDoesNotThrow(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $result = $this->repository->addToFavorites(999, 9990);
        
        $this->assertTrue($result);
        $this->assertEquals(1, $this->repository->getFavoritesCount(999));
    }

    /**
     * Test adding multiple movies to favorites
     */
    public function testAddMultipleMoviesToFavorites(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $this->repository->addToFavorites(999, 9991);
        $this->repository->addToFavorites(999, 9992);
        
        $this->assertEquals(3, $this->repository->getFavoritesCount(999));
    }

    /**
     * Test adding with invalid user ID throws exception
     */
    public function testAddToFavoritesInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->addToFavorites(0, 9990);
    }

    /**
     * Test adding with negative user ID throws exception
     */
    public function testAddToFavoritesNegativeUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->addToFavorites(-1, 9990);
    }

    /**
     * Test adding with invalid movie ID throws exception
     */
    public function testAddToFavoritesInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->addToFavorites(999, 0);
    }

    /**
     * Test adding with negative movie ID throws exception
     */
    public function testAddToFavoritesNegativeMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->addToFavorites(999, -5);
    }

    /**
     * Test SQL injection prevention in addToFavorites
     */
    public function testAddToFavoritesSQLInjectionPrevention(): void
    {
        // Even with malicious input, type hints ensure integers only
        $this->expectException(TypeError::class);
        
        // @phpstan-ignore-next-line - Testing type safety
        $this->repository->addToFavorites("999' OR '1'='1", 9990);
    }

    // =================================================================
    // REMOVE FROM FAVORITES TESTS
    // =================================================================

    /**
     * Test removing a movie from favorites successfully
     */
    public function testRemoveFromFavoritesSuccess(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $result = $this->repository->removeFromFavorites(999, 9990);
        
        $this->assertTrue($result);
        $this->assertFalse($this->repository->isFavorite(999, 9990));
    }

    /**
     * Test removing non-existent favorite returns true
     */
    public function testRemoveNonExistentFavoriteReturnsTrue(): void
    {
        $result = $this->repository->removeFromFavorites(999, 9990);
        
        $this->assertTrue($result);
    }

    /**
     * Test removing with invalid user ID throws exception
     */
    public function testRemoveFromFavoritesInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->removeFromFavorites(0, 9990);
    }

    /**
     * Test removing with invalid movie ID throws exception
     */
    public function testRemoveFromFavoritesInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->removeFromFavorites(999, -1);
    }

    // =================================================================
    // GET FAVORITES TESTS
    // =================================================================

    /**
     * Test getting favorites for user with no favorites
     */
    public function testGetFavoritesEmptyList(): void
    {
        $favorites = $this->repository->getFavorites(999);
        
        $this->assertIsArray($favorites);
        $this->assertEmpty($favorites);
    }

    /**
     * Test getting favorites returns correct data structure
     */
    public function testGetFavoritesReturnsCorrectStructure(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $favorites = $this->repository->getFavorites(999);
        
        $this->assertCount(1, $favorites);
        $this->assertArrayHasKey('id', $favorites[0]);
        $this->assertArrayHasKey('title', $favorites[0]);
        $this->assertArrayHasKey('category', $favorites[0]);
        $this->assertArrayHasKey('score', $favorites[0]);
        $this->assertArrayHasKey('favorited_at', $favorites[0]);
    }

    /**
     * Test getting favorites returns movies in correct order (newest first)
     */
    public function testGetFavoritesOrderedByNewest(): void
    {
        $this->repository->addToFavorites(999, 9990);
        sleep(1); // Ensure different timestamps
        $this->repository->addToFavorites(999, 9991);
        
        $favorites = $this->repository->getFavorites(999);
        
        $this->assertEquals('Test Movie 2', $favorites[0]['title']);
        $this->assertEquals('Test Movie 1', $favorites[1]['title']);
    }

    /**
     * Test getting favorites for different users are isolated
     */
    public function testGetFavoritesUserIsolation(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $this->repository->addToFavorites(998, 9991);
        
        $user999Favorites = $this->repository->getFavorites(999);
        $user998Favorites = $this->repository->getFavorites(998);
        
        $this->assertCount(1, $user999Favorites);
        $this->assertCount(1, $user998Favorites);
        $this->assertEquals(9990, $user999Favorites[0]['id']);
        $this->assertEquals(9991, $user998Favorites[0]['id']);
    }

    /**
     * Test getting favorites with invalid user ID throws exception
     */
    public function testGetFavoritesInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->getFavorites(0);
    }

    // =================================================================
    // IS FAVORITE TESTS
    // =================================================================

    /**
     * Test isFavorite returns true for favorited movie
     */
    public function testIsFavoriteReturnsTrue(): void
    {
        $this->repository->addToFavorites(999, 9990);
        
        $this->assertTrue($this->repository->isFavorite(999, 9990));
    }

    /**
     * Test isFavorite returns false for non-favorited movie
     */
    public function testIsFavoriteReturnsFalse(): void
    {
        $this->assertFalse($this->repository->isFavorite(999, 9990));
    }

    /**
     * Test isFavorite is user-specific
     */
    public function testIsFavoriteUserSpecific(): void
    {
        $this->repository->addToFavorites(999, 9990);
        
        $this->assertTrue($this->repository->isFavorite(999, 9990));
        $this->assertFalse($this->repository->isFavorite(998, 9990));
    }

    /**
     * Test isFavorite with invalid user ID throws exception
     */
    public function testIsFavoriteInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->isFavorite(-1, 9990);
    }

    /**
     * Test isFavorite with invalid movie ID throws exception
     */
    public function testIsFavoriteInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->isFavorite(999, 0);
    }

    // =================================================================
    // GET FAVORITES COUNT TESTS
    // =================================================================

    /**
     * Test getting count for user with no favorites
     */
    public function testGetFavoritesCountZero(): void
    {
        $count = $this->repository->getFavoritesCount(999);
        
        $this->assertEquals(0, $count);
    }

    /**
     * Test getting count after adding favorites
     */
    public function testGetFavoritesCountAfterAdding(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $this->repository->addToFavorites(999, 9991);
        $this->repository->addToFavorites(999, 9992);
        
        $count = $this->repository->getFavoritesCount(999);
        
        $this->assertEquals(3, $count);
    }

    /**
     * Test getting count after removing favorites
     */
    public function testGetFavoritesCountAfterRemoving(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $this->repository->addToFavorites(999, 9991);
        $this->repository->removeFromFavorites(999, 9990);
        
        $count = $this->repository->getFavoritesCount(999);
        
        $this->assertEquals(1, $count);
    }

    /**
     * Test count is user-specific
     */
    public function testGetFavoritesCountUserSpecific(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $this->repository->addToFavorites(999, 9991);
        $this->repository->addToFavorites(998, 9992);
        
        $this->assertEquals(2, $this->repository->getFavoritesCount(999));
        $this->assertEquals(1, $this->repository->getFavoritesCount(998));
    }

    /**
     * Test getting count with invalid user ID throws exception
     */
    public function testGetFavoritesCountInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->getFavoritesCount(0);
    }

    // =================================================================
    // EDGE CASES AND ERROR HANDLING
    // =================================================================

    /**
     * Test that favorites persist across repository instances
     */
    public function testFavoritesPersistAcrossInstances(): void
    {
        $this->repository->addToFavorites(999, 9990);
        
        $newRepository = new FavoritesRepository($this->database);
        $this->assertTrue($newRepository->isFavorite(999, 9990));
    }

    /**
     * Test handling of large user IDs
     */
    public function testLargeUserIds(): void
    {
        $largeUserId = 2147483647; // Max int32
        
        $result = $this->repository->addToFavorites($largeUserId, 9990);
        $this->assertTrue($result);
        $this->assertTrue($this->repository->isFavorite($largeUserId, 9990));
        
        // Cleanup
        $this->repository->removeFromFavorites($largeUserId, 9990);
    }

    /**
     * Test handling of large movie IDs
     */
    public function testLargeMovieIds(): void
    {
        // Insert a movie with large ID
        $largeMovieId = 2147483646;
        $sql = "INSERT INTO movies (id, title, category, score, trailer_url) VALUES 
                ($largeMovieId, 'Large ID Movie', 'Action', 8.0, 'http://example.com/trailer')
                ON DUPLICATE KEY UPDATE title=VALUES(title)";
        $this->pdo->exec($sql);
        
        $result = $this->repository->addToFavorites(999, $largeMovieId);
        $this->assertTrue($result);
        
        // Cleanup
        $this->pdo->exec("DELETE FROM movies WHERE id = $largeMovieId");
    }

    /**
     * Test concurrent additions from different users
     */
    public function testConcurrentAdditions(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $this->repository->addToFavorites(998, 9990);
        $this->repository->addToFavorites(997, 9990);
        
        $this->assertTrue($this->repository->isFavorite(999, 9990));
        $this->assertTrue($this->repository->isFavorite(998, 9990));
        $this->assertTrue($this->repository->isFavorite(997, 9990));
    }

    /**
     * Test that timestamp is automatically set
     */
    public function testTimestampAutoSet(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $favorites = $this->repository->getFavorites(999);
        
        $this->assertNotNull($favorites[0]['favorited_at']);
        $this->assertNotEmpty($favorites[0]['favorited_at']);
    }

    /**
     * Test getFavorites returns all movie fields
     */
    public function testGetFavoritesReturnsAllMovieFields(): void
    {
        $this->repository->addToFavorites(999, 9990);
        $favorites = $this->repository->getFavorites(999);
        
        $expectedFields = ['id', 'title', 'category', 'score', 'trailer_url', 
                          'description', 'release_year', 'runtime_minutes', 'director', 
                          'actors', 'poster_url', 'backdrop_url', 'imdb_rating', 
                          'user_rating', 'votes_count', 'created_at', 'updated_at', 'favorited_at'];
        
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $favorites[0], "Missing field: $field");
        }
    }
}
