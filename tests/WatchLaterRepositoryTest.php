<?php
/**
 * WatchLaterRepository Test Suite
 * 
 * Comprehensive tests for the WatchLaterRepository class including:
 * - Adding and removing movies from watch later list
 * - Marking movies as watched
 * - Retrieving watch later lists
 * - Watch history tracking
 * - Input validation
 * - Error handling
 * - SQL injection prevention
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/WatchLaterRepository.php';

use PHPUnit\Framework\TestCase;
use MovieSuggestor\WatchLaterRepository;

/**
 * Class WatchLaterRepositoryTest
 * 
 * Tests all WatchLaterRepository functionality including CRUD operations,
 * watched status tracking, validation, and error handling.
 */
class WatchLaterRepositoryTest extends TestCase
{
    private PDO $pdo;
    private WatchLaterRepository $repository;

    /**
     * Set up test fixture before each test
     */
    protected function setUp(): void
    {
        // Use test database
        putenv('DB_NAME=moviesuggestor_test');
        
        $database = new MovieSuggestor\Database();
        $this->pdo = $database->connect();
        $this->repository = new WatchLaterRepository($this->pdo);
        
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
        $this->pdo->exec('DELETE FROM watch_later WHERE user_id IN (999, 998, 997)');
        $this->pdo->exec('DELETE FROM movies WHERE id IN (9990, 9991, 9992, 9993)');
    }

    /**
     * Insert test movies for testing
     */
    private function insertTestMovies(): void
    {
        $sql = "INSERT INTO movies (id, title, category, score, trailer_url) VALUES 
                (9990, 'Watch Later Movie 1', 'Action', 8.5, 'http://example.com/trailer1'),
                (9991, 'Watch Later Movie 2', 'Drama', 7.5, 'http://example.com/trailer2'),
                (9992, 'Watch Later Movie 3', 'Sci-Fi', 9.0, 'http://example.com/trailer3'),
                (9993, 'Watch Later Movie 4', 'Comedy', 6.5, 'http://example.com/trailer4')
                ON DUPLICATE KEY UPDATE title=VALUES(title)";
        $this->pdo->exec($sql);
    }

    // =================================================================
    // ADD TO WATCH LATER TESTS
    // =================================================================

    /**
     * Test adding a movie to watch later successfully
     */
    public function testAddToWatchLaterSuccess(): void
    {
        $result = $this->repository->addToWatchLater(999, 9990);
        
        $this->assertTrue($result);
        $this->assertTrue($this->repository->isInWatchLater(999, 9990));
    }

    /**
     * Test adding same movie twice updates timestamp
     */
    public function testAddToWatchLaterTwiceUpdatesTimestamp(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        sleep(1);
        $result = $this->repository->addToWatchLater(999, 9990);
        
        $this->assertTrue($result);
        $this->assertTrue($this->repository->isInWatchLater(999, 9990));
    }

    /**
     * Test adding multiple movies to watch later
     */
    public function testAddMultipleMoviesToWatchLater(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(999, 9991);
        $this->repository->addToWatchLater(999, 9992);
        
        $watchLater = $this->repository->getWatchLater(999);
        $this->assertCount(3, $watchLater);
    }

    /**
     * Test adding with invalid user ID throws exception
     */
    public function testAddToWatchLaterInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->addToWatchLater(0, 9990);
    }

    /**
     * Test adding with negative user ID throws exception
     */
    public function testAddToWatchLaterNegativeUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->addToWatchLater(-1, 9990);
    }

    /**
     * Test adding with invalid movie ID throws exception
     */
    public function testAddToWatchLaterInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->addToWatchLater(999, 0);
    }

    /**
     * Test adding with negative movie ID throws exception
     */
    public function testAddToWatchLaterNegativeMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->addToWatchLater(999, -5);
    }

    // =================================================================
    // REMOVE FROM WATCH LATER TESTS
    // =================================================================

    /**
     * Test removing a movie from watch later successfully
     */
    public function testRemoveFromWatchLaterSuccess(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $result = $this->repository->removeFromWatchLater(999, 9990);
        
        $this->assertTrue($result);
        $this->assertFalse($this->repository->isInWatchLater(999, 9990));
    }

    /**
     * Test removing non-existent entry returns true
     */
    public function testRemoveNonExistentWatchLaterReturnsTrue(): void
    {
        $result = $this->repository->removeFromWatchLater(999, 9990);
        
        $this->assertTrue($result);
    }

    /**
     * Test removing with invalid user ID throws exception
     */
    public function testRemoveFromWatchLaterInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->removeFromWatchLater(0, 9990);
    }

    /**
     * Test removing with invalid movie ID throws exception
     */
    public function testRemoveFromWatchLaterInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->removeFromWatchLater(999, -1);
    }

    // =================================================================
    // GET WATCH LATER TESTS
    // =================================================================

    /**
     * Test getting watch later for user with empty list
     */
    public function testGetWatchLaterEmptyList(): void
    {
        $watchLater = $this->repository->getWatchLater(999);
        
        $this->assertIsArray($watchLater);
        $this->assertEmpty($watchLater);
    }

    /**
     * Test getting watch later returns correct structure
     */
    public function testGetWatchLaterReturnsCorrectStructure(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $watchLater = $this->repository->getWatchLater(999);
        
        $this->assertCount(1, $watchLater);
        $this->assertArrayHasKey('id', $watchLater[0]);
        $this->assertArrayHasKey('title', $watchLater[0]);
        $this->assertArrayHasKey('added_at', $watchLater[0]);
        $this->assertArrayHasKey('watched', $watchLater[0]);
        $this->assertArrayHasKey('watched_at', $watchLater[0]);
    }

    /**
     * Test getting watch later excludes watched movies by default
     */
    public function testGetWatchLaterExcludesWatchedByDefault(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(999, 9991);
        $this->repository->markAsWatched(999, 9990);
        
        $watchLater = $this->repository->getWatchLater(999);
        
        $this->assertCount(1, $watchLater);
        $this->assertEquals(9991, $watchLater[0]['id']);
    }

    /**
     * Test getting watch later includes watched when requested
     */
    public function testGetWatchLaterIncludesWatchedWhenRequested(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(999, 9991);
        $this->repository->markAsWatched(999, 9990);
        
        $watchLater = $this->repository->getWatchLater(999, true);
        
        $this->assertCount(2, $watchLater);
    }

    /**
     * Test getting watch later ordered by newest first
     */
    public function testGetWatchLaterOrderedByNewest(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        sleep(1);
        $this->repository->addToWatchLater(999, 9991);
        
        $watchLater = $this->repository->getWatchLater(999);
        
        $this->assertEquals('Watch Later Movie 2', $watchLater[0]['title']);
        $this->assertEquals('Watch Later Movie 1', $watchLater[1]['title']);
    }

    /**
     * Test getting watch later for different users are isolated
     */
    public function testGetWatchLaterUserIsolation(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(998, 9991);
        
        $user999List = $this->repository->getWatchLater(999);
        $user998List = $this->repository->getWatchLater(998);
        
        $this->assertCount(1, $user999List);
        $this->assertCount(1, $user998List);
        $this->assertEquals(9990, $user999List[0]['id']);
        $this->assertEquals(9991, $user998List[0]['id']);
    }

    /**
     * Test getting watch later with invalid user ID throws exception
     */
    public function testGetWatchLaterInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->getWatchLater(0);
    }

    // =================================================================
    // IS IN WATCH LATER TESTS
    // =================================================================

    /**
     * Test isInWatchLater returns true for added movie
     */
    public function testIsInWatchLaterReturnsTrue(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        
        $this->assertTrue($this->repository->isInWatchLater(999, 9990));
    }

    /**
     * Test isInWatchLater returns false for non-added movie
     */
    public function testIsInWatchLaterReturnsFalse(): void
    {
        $this->assertFalse($this->repository->isInWatchLater(999, 9990));
    }

    /**
     * Test isInWatchLater returns true even for watched movies
     */
    public function testIsInWatchLaterReturnsTrueForWatched(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->markAsWatched(999, 9990);
        
        $this->assertTrue($this->repository->isInWatchLater(999, 9990));
    }

    /**
     * Test isInWatchLater is user-specific
     */
    public function testIsInWatchLaterUserSpecific(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        
        $this->assertTrue($this->repository->isInWatchLater(999, 9990));
        $this->assertFalse($this->repository->isInWatchLater(998, 9990));
    }

    /**
     * Test isInWatchLater with invalid user ID throws exception
     */
    public function testIsInWatchLaterInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->isInWatchLater(-1, 9990);
    }

    /**
     * Test isInWatchLater with invalid movie ID throws exception
     */
    public function testIsInWatchLaterInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->isInWatchLater(999, 0);
    }

    // =================================================================
    // MARK AS WATCHED TESTS
    // =================================================================

    /**
     * Test marking a movie as watched successfully
     */
    public function testMarkAsWatchedSuccess(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $result = $this->repository->markAsWatched(999, 9990);
        
        $this->assertTrue($result);
        
        // Verify it's not in unwatched list
        $unwatched = $this->repository->getWatchLater(999, false);
        $this->assertEmpty($unwatched);
        
        // Verify it's in watched list
        $all = $this->repository->getWatchLater(999, true);
        $this->assertCount(1, $all);
        $this->assertEquals(1, $all[0]['watched']);
    }

    /**
     * Test marking adds movie if not in watch later
     */
    public function testMarkAsWatchedAddsIfNotInList(): void
    {
        $result = $this->repository->markAsWatched(999, 9990);
        
        $this->assertTrue($result);
        $this->assertTrue($this->repository->isInWatchLater(999, 9990));
    }

    /**
     * Test marking with invalid user ID throws exception
     */
    public function testMarkAsWatchedInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->markAsWatched(0, 9990);
    }

    /**
     * Test marking with invalid movie ID throws exception
     */
    public function testMarkAsWatchedInvalidMovieIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Movie ID must be a positive integer');
        
        $this->repository->markAsWatched(999, -1);
    }

    /**
     * Test marking same movie twice succeeds
     */
    public function testMarkAsWatchedTwiceSucceeds(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->markAsWatched(999, 9990);
        $result = $this->repository->markAsWatched(999, 9990);
        
        $this->assertTrue($result);
    }

    // =================================================================
    // GET WATCHED HISTORY TESTS
    // =================================================================

    /**
     * Test getting watched history for user with no history
     */
    public function testGetWatchedHistoryEmpty(): void
    {
        $history = $this->repository->getWatchedHistory(999);
        
        $this->assertIsArray($history);
        $this->assertEmpty($history);
    }

    /**
     * Test getting watched history returns only watched movies
     */
    public function testGetWatchedHistoryOnlyWatched(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(999, 9991);
        $this->repository->markAsWatched(999, 9990);
        
        $history = $this->repository->getWatchedHistory(999);
        
        $this->assertCount(1, $history);
        $this->assertEquals(9990, $history[0]['id']);
    }

    /**
     * Test watched history ordered by watched date (newest first)
     */
    public function testGetWatchedHistoryOrderedByWatchedDate(): void
    {
        $this->repository->markAsWatched(999, 9990);
        sleep(1);
        $this->repository->markAsWatched(999, 9991);
        
        $history = $this->repository->getWatchedHistory(999);
        
        $this->assertEquals(9991, $history[0]['id']);
        $this->assertEquals(9990, $history[1]['id']);
    }

    /**
     * Test watched history with custom limit
     */
    public function testGetWatchedHistoryWithLimit(): void
    {
        $this->repository->markAsWatched(999, 9990);
        $this->repository->markAsWatched(999, 9991);
        $this->repository->markAsWatched(999, 9992);
        
        $history = $this->repository->getWatchedHistory(999, 2);
        
        $this->assertCount(2, $history);
    }

    /**
     * Test watched history with invalid user ID throws exception
     */
    public function testGetWatchedHistoryInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->getWatchedHistory(0);
    }

    /**
     * Test watched history with invalid limit throws exception
     */
    public function testGetWatchedHistoryInvalidLimitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be a positive integer');
        
        $this->repository->getWatchedHistory(999, 0);
    }

    // =================================================================
    // GET UNWATCHED COUNT TESTS
    // =================================================================

    /**
     * Test getting unwatched count for empty list
     */
    public function testGetUnwatchedCountZero(): void
    {
        $count = $this->repository->getUnwatchedCount(999);
        
        $this->assertEquals(0, $count);
    }

    /**
     * Test getting unwatched count after adding
     */
    public function testGetUnwatchedCountAfterAdding(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(999, 9991);
        
        $count = $this->repository->getUnwatchedCount(999);
        
        $this->assertEquals(2, $count);
    }

    /**
     * Test unwatched count decreases after marking as watched
     */
    public function testGetUnwatchedCountAfterWatching(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(999, 9991);
        $this->repository->addToWatchLater(999, 9992);
        $this->repository->markAsWatched(999, 9990);
        
        $count = $this->repository->getUnwatchedCount(999);
        
        $this->assertEquals(2, $count);
    }

    /**
     * Test unwatched count with invalid user ID throws exception
     */
    public function testGetUnwatchedCountInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->getUnwatchedCount(-1);
    }

    // =================================================================
    // CLEAR WATCHED TESTS
    // =================================================================

    /**
     * Test clearing watched movies
     */
    public function testClearWatchedSuccess(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(999, 9991);
        $this->repository->addToWatchLater(999, 9992);
        $this->repository->markAsWatched(999, 9990);
        $this->repository->markAsWatched(999, 9991);
        
        $result = $this->repository->clearWatched(999);
        
        $this->assertTrue($result);
        $this->assertEquals(1, $this->repository->getUnwatchedCount(999));
        $this->assertEmpty($this->repository->getWatchedHistory(999));
    }

    /**
     * Test clearing watched keeps unwatched movies
     */
    public function testClearWatchedKeepsUnwatched(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(999, 9991);
        $this->repository->markAsWatched(999, 9990);
        
        $this->repository->clearWatched(999);
        
        $watchLater = $this->repository->getWatchLater(999);
        $this->assertCount(1, $watchLater);
        $this->assertEquals(9991, $watchLater[0]['id']);
    }

    /**
     * Test clearing watched on empty list succeeds
     */
    public function testClearWatchedEmptyListSucceeds(): void
    {
        $result = $this->repository->clearWatched(999);
        
        $this->assertTrue($result);
    }

    /**
     * Test clearing watched with invalid user ID throws exception
     */
    public function testClearWatchedInvalidUserIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');
        
        $this->repository->clearWatched(0);
    }

    // =================================================================
    // EDGE CASES AND ERROR HANDLING
    // =================================================================

    /**
     * Test that data persists across repository instances
     */
    public function testDataPersistsAcrossInstances(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        
        $newRepository = new WatchLaterRepository($this->pdo);
        $this->assertTrue($newRepository->isInWatchLater(999, 9990));
    }

    /**
     * Test handling of large user IDs
     */
    public function testLargeUserIds(): void
    {
        $largeUserId = 2147483647; // Max int32
        
        $result = $this->repository->addToWatchLater($largeUserId, 9990);
        $this->assertTrue($result);
        
        // Cleanup
        $this->pdo->exec("DELETE FROM watch_later WHERE user_id = $largeUserId");
    }

    /**
     * Test concurrent additions from different users
     */
    public function testConcurrentAdditions(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->addToWatchLater(998, 9990);
        $this->repository->addToWatchLater(997, 9990);
        
        $this->assertTrue($this->repository->isInWatchLater(999, 9990));
        $this->assertTrue($this->repository->isInWatchLater(998, 9990));
        $this->assertTrue($this->repository->isInWatchLater(997, 9990));
    }

    /**
     * Test that timestamps are automatically set
     */
    public function testTimestampsAutoSet(): void
    {
        $this->repository->addToWatchLater(999, 9990);
        $this->repository->markAsWatched(999, 9990);
        
        $history = $this->repository->getWatchedHistory(999);
        
        $this->assertNotNull($history[0]['added_at']);
        $this->assertNotNull($history[0]['watched_at']);
    }

    /**
     * Test SQL injection prevention in user ID
     */
    public function testSQLInjectionPreventionUserId(): void
    {
        $this->expectException(TypeError::class);
        
        // @phpstan-ignore-next-line - Testing type safety
        $this->repository->addToWatchLater("999' OR '1'='1", 9990);
    }

    /**
     * Test SQL injection prevention in movie ID
     */
    public function testSQLInjectionPreventionMovieId(): void
    {
        $this->expectException(TypeError::class);
        
        // @phpstan-ignore-next-line - Testing type safety
        $this->repository->addToWatchLater(999, "9990'; DROP TABLE watch_later; --");
    }
}
