<?php

namespace MovieSuggestor\Tests;

use PHPUnit\Framework\TestCase;
use MovieSuggestor\Database;
use MovieSuggestor\MovieRepository;

class MovieRepositoryTest extends TestCase
{
    private Database $database;
    private MovieRepository $repository;

    protected function setUp(): void
    {
        $this->database = new Database();
        $this->repository = new MovieRepository($this->database);
    }

    public function testDatabaseConnection(): void
    {
        $connection = $this->database->connect();
        $this->assertNotNull($connection, 'Database connection should not be null');
    }

    public function testGetAllCategories(): void
    {
        $categories = $this->repository->getAllCategories();
        
        $this->assertIsArray($categories, 'Categories should be an array');
        $this->assertNotEmpty($categories, 'Categories should not be empty');
        $this->assertContains('Action', $categories, 'Should contain Action category');
        $this->assertContains('Drama', $categories, 'Should contain Drama category');
    }

    public function testFindByFiltersWithNoFilters(): void
    {
        $movies = $this->repository->findByFilters();
        
        $this->assertIsArray($movies, 'Movies should be an array');
        $this->assertNotEmpty($movies, 'Should return movies when no filters applied');
    }

    public function testFindByFiltersWithCategory(): void
    {
        $movies = $this->repository->findByFilters('Action');
        
        $this->assertIsArray($movies, 'Movies should be an array');
        
        foreach ($movies as $movie) {
            $this->assertEquals('Action', $movie['category'], 'All movies should be Action category');
        }
    }

    public function testFindByFiltersWithMinScore(): void
    {
        $minScore = 9.0;
        $movies = $this->repository->findByFilters('', $minScore);
        
        $this->assertIsArray($movies, 'Movies should be an array');
        
        foreach ($movies as $movie) {
            $this->assertGreaterThanOrEqual($minScore, $movie['score'], "Movie score should be >= {$minScore}");
        }
    }

    public function testFindByFiltersWithCategoryAndScore(): void
    {
        $movies = $this->repository->findByFilters('Drama', 9.0);
        
        $this->assertIsArray($movies, 'Movies should be an array');
        
        foreach ($movies as $movie) {
            $this->assertEquals('Drama', $movie['category'], 'Category should match');
            $this->assertGreaterThanOrEqual(9.0, $movie['score'], 'Score should be >= 9.0');
        }
    }

    public function testFindByFiltersReturnsEmptyForNoMatches(): void
    {
        $movies = $this->repository->findByFilters('NonExistentCategory', 10.0);
        
        $this->assertIsArray($movies, 'Should return array even with no results');
        $this->assertEmpty($movies, 'Should return empty array when no matches');
    }

    public function testMovieHasRequiredFields(): void
    {
        $movies = $this->repository->findByFilters();
        
        $this->assertNotEmpty($movies, 'Should have at least one movie');
        
        $movie = $movies[0];
        $this->assertArrayHasKey('id', $movie, 'Movie should have id');
        $this->assertArrayHasKey('title', $movie, 'Movie should have title');
        $this->assertArrayHasKey('category', $movie, 'Movie should have category');
        $this->assertArrayHasKey('score', $movie, 'Movie should have score');
        $this->assertArrayHasKey('trailer_url', $movie, 'Movie should have trailer_url');
        $this->assertArrayHasKey('description', $movie, 'Movie should have description');
    }

    public function testMoviesAreOrderedByScoreDescending(): void
    {
        $movies = $this->repository->findByFilters();
        
        $this->assertNotEmpty($movies, 'Should have movies');
        
        $previousScore = PHP_FLOAT_MAX;
        foreach ($movies as $movie) {
            $this->assertLessThanOrEqual($previousScore, $movie['score'], 'Movies should be ordered by score descending');
            $previousScore = $movie['score'];
        }
    }

    public function testFindByFiltersWithNegativeScore(): void
    {
        $movies = $this->repository->findByFilters('', -5.0);
        
        $this->assertIsArray($movies, 'Should handle negative scores gracefully');
        // Input validation should clamp negative to 0, so all movies should be returned
        $this->assertNotEmpty($movies, 'Should return all movies when clamped to 0');
    }

    public function testFindByFiltersWithExcessiveScore(): void
    {
        $movies = $this->repository->findByFilters('', 999.9);
        
        $this->assertIsArray($movies, 'Should handle excessive scores gracefully');
        // Input validation should clamp to max 10.0
        $this->assertEmpty($movies, 'Should return only movies with score >= 10.0 (none exist)');
    }

    public function testFindByFiltersWithScoreAtBoundaries(): void
    {
        // Test exact boundary values
        $moviesAtZero = $this->repository->findByFilters('', 0.0);
        $this->assertNotEmpty($moviesAtZero, 'Should return movies at score 0.0');
        
        $moviesAtTen = $this->repository->findByFilters('', 10.0);
        $this->assertIsArray($moviesAtTen, 'Should handle score 10.0');
    }

    public function testSqlInjectionPrevention(): void
    {
        $maliciousCategory = "Action' OR '1'='1";
        $movies = $this->repository->findByFilters($maliciousCategory);
        
        $this->assertIsArray($movies, 'Should not crash on SQL injection attempt');
        // Should return empty since no category matches exactly
        $this->assertEmpty($movies, 'Should not return movies for SQL injection attempt');
    }

    public function testCategoryWithWhitespace(): void
    {
        $categoryWithSpaces = '  Action  ';
        $movies = $this->repository->findByFilters($categoryWithSpaces);
        
        $this->assertIsArray($movies, 'Should handle category with whitespace');
        // After trimming, should match Action category
        $this->assertNotEmpty($movies, 'Should find Action movies after trimming');
        
        foreach ($movies as $movie) {
            $this->assertEquals('Action', $movie['category'], 'All movies should be Action category');
        }
    }

    public function testEmptyCategoryString(): void
    {
        $movies = $this->repository->findByFilters('');
        
        $this->assertIsArray($movies, 'Empty category should return array');
        $this->assertNotEmpty($movies, 'Empty category should return all movies');
    }

    public function testScorePrecision(): void
    {
        $movies = $this->repository->findByFilters('', 8.5);
        
        $this->assertIsArray($movies, 'Should handle decimal scores');
        
        foreach ($movies as $movie) {
            $this->assertGreaterThanOrEqual(8.5, $movie['score'], 'All movies should have score >= 8.5');
        }
    }

    public function testMultipleFiltersWithNoMatches(): void
    {
        // Very high score with specific category - should return empty
        $movies = $this->repository->findByFilters('Animation', 9.5);
        
        $this->assertIsArray($movies, 'Should return array even with no matches');
        $this->assertEmpty($movies, 'Should return empty array for impossible combination');
    }
}
