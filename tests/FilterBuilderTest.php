<?php
/**
 * FilterBuilder Test Suite
 * 
 * Comprehensive tests for the FilterBuilder class including:
 * - Fluent interface functionality
 * - Input validation
 * - SQL generation
 * - Parameter binding
 * - Edge cases and error handling
 */

require_once __DIR__ . '/../src/FilterBuilder.php';

use PHPUnit\Framework\TestCase;

/**
 * Class FilterBuilderTest
 * 
 * Tests all FilterBuilder functionality including query building,
 * validation, and SQL safety features.
 */
class FilterBuilderTest extends TestCase
{
    /**
     * @var FilterBuilder
     */
    private FilterBuilder $builder;
    
    /**
     * Set up test fixture before each test
     */
    protected function setUp(): void
    {
        $this->builder = new FilterBuilder();
    }
    
    /**
     * Test basic query building with no filters
     */
    public function testBuildEmptyQuery(): void
    {
        $result = $this->builder->build();
        
        $this->assertEquals('SELECT * FROM movies', $result['sql']);
        $this->assertEmpty($result['params']);
    }
    
    /**
     * Test filtering by single category
     */
    public function testWithSingleCategory(): void
    {
        $result = $this->builder
            ->withCategories(['Action'])
            ->build();
        
        $this->assertStringContainsString('category IN (?)', $result['sql']);
        $this->assertEquals(['Action'], $result['params']);
    }
    
    /**
     * Test filtering by multiple categories
     */
    public function testWithMultipleCategories(): void
    {
        $result = $this->builder
            ->withCategories(['Action', 'Sci-Fi', 'Drama'])
            ->build();
        
        $this->assertStringContainsString('category IN (?,?,?)', $result['sql']);
        $this->assertEquals(['Action', 'Sci-Fi', 'Drama'], $result['params']);
    }
    
    /**
     * Test empty categories array throws exception
     */
    public function testWithEmptyCategoriesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Categories array cannot be empty');
        
        $this->builder->withCategories([]);
    }
    
    /**
     * Test invalid category value throws exception
     */
    public function testWithInvalidCategoryThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All categories must be non-empty strings');
        
        $this->builder->withCategories(['Action', '', 'Drama']);
    }
    
    /**
     * Test score range filtering with minimum only
     */
    public function testWithScoreRangeMinimum(): void
    {
        $result = $this->builder
            ->withScoreRange(8.0, null)
            ->build();
        
        $this->assertStringContainsString('score >= ?', $result['sql']);
        $this->assertEquals([8.0], $result['params']);
    }
    
    /**
     * Test score range filtering with maximum only
     */
    public function testWithScoreRangeMaximum(): void
    {
        $result = $this->builder
            ->withScoreRange(null, 9.5)
            ->build();
        
        $this->assertStringContainsString('score <= ?', $result['sql']);
        $this->assertEquals([9.5], $result['params']);
    }
    
    /**
     * Test score range filtering with both min and max
     */
    public function testWithScoreRangeBoth(): void
    {
        $result = $this->builder
            ->withScoreRange(7.5, 9.5)
            ->build();
        
        $this->assertStringContainsString('score >= ?', $result['sql']);
        $this->assertStringContainsString('score <= ?', $result['sql']);
        $this->assertEquals([7.5, 9.5], $result['params']);
    }
    
    /**
     * Test invalid minimum score throws exception
     */
    public function testWithInvalidMinimumScoreThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum score must be between 0.0 and 10.0');
        
        $this->builder->withScoreRange(-1.0, 10.0);
    }
    
    /**
     * Test invalid maximum score throws exception
     */
    public function testWithInvalidMaximumScoreThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum score must be between 0.0 and 10.0');
        
        $this->builder->withScoreRange(0.0, 11.0);
    }
    
    /**
     * Test minimum greater than maximum throws exception
     */
    public function testWithScoreRangeMinGreaterThanMaxThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum score cannot be greater than maximum score');
        
        $this->builder->withScoreRange(9.0, 7.0);
    }
    
    /**
     * Test year range filtering with start only
     */
    public function testWithYearRangeStart(): void
    {
        $result = $this->builder
            ->withYearRange(2000, null)
            ->build();
        
        $this->assertStringContainsString('release_year >= ?', $result['sql']);
        $this->assertEquals([2000], $result['params']);
    }
    
    /**
     * Test year range filtering with end only
     */
    public function testWithYearRangeEnd(): void
    {
        $result = $this->builder
            ->withYearRange(null, 2020)
            ->build();
        
        $this->assertStringContainsString('release_year <= ?', $result['sql']);
        $this->assertEquals([2020], $result['params']);
    }
    
    /**
     * Test year range filtering with both start and end
     */
    public function testWithYearRangeBoth(): void
    {
        $result = $this->builder
            ->withYearRange(2000, 2020)
            ->build();
        
        $this->assertStringContainsString('release_year >= ?', $result['sql']);
        $this->assertStringContainsString('release_year <= ?', $result['sql']);
        $this->assertEquals([2000, 2020], $result['params']);
    }
    
    /**
     * Test invalid start year throws exception
     */
    public function testWithInvalidStartYearThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Start year must be between 1888 and 2100');
        
        $this->builder->withYearRange(1800, 2020);
    }
    
    /**
     * Test invalid end year throws exception
     */
    public function testWithInvalidEndYearThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('End year must be between 1888 and 2100');
        
        $this->builder->withYearRange(2000, 2150);
    }
    
    /**
     * Test start year greater than end year throws exception
     */
    public function testWithYearRangeStartGreaterThanEndThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Start year cannot be greater than end year');
        
        $this->builder->withYearRange(2020, 2000);
    }
    
    /**
     * Test runtime range filtering
     */
    public function testWithRuntimeRange(): void
    {
        $result = $this->builder
            ->withRuntimeRange(90, 150)
            ->build();
        
        $this->assertStringContainsString('runtime_minutes >= ?', $result['sql']);
        $this->assertStringContainsString('runtime_minutes <= ?', $result['sql']);
        $this->assertEquals([90, 150], $result['params']);
    }
    
    /**
     * Test invalid minimum runtime throws exception
     */
    public function testWithInvalidMinimumRuntimeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum runtime must be greater than 0');
        
        $this->builder->withRuntimeRange(0, 150);
    }
    
    /**
     * Test search text filtering
     */
    public function testWithSearchText(): void
    {
        $result = $this->builder
            ->withSearchText('space adventure')
            ->build();
        
        $this->assertStringContainsString('title LIKE ?', $result['sql']);
        $this->assertStringContainsString('description LIKE ?', $result['sql']);
        $this->assertStringContainsString('director LIKE ?', $result['sql']);
        $this->assertStringContainsString('actors LIKE ?', $result['sql']);
        
        // Should have 4 parameters (one for each field)
        $this->assertCount(4, $result['params']);
        $this->assertEquals('%space adventure%', $result['params'][0]);
    }
    
    /**
     * Test search text with empty string is ignored
     */
    public function testWithEmptySearchText(): void
    {
        $result = $this->builder
            ->withSearchText('')
            ->build();
        
        $this->assertEquals('SELECT * FROM movies', $result['sql']);
        $this->assertEmpty($result['params']);
    }
    
    /**
     * Test search text exceeding maximum length throws exception
     */
    public function testWithTooLongSearchTextThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Search text cannot exceed 500 characters');
        
        $longText = str_repeat('a', 501);
        $this->builder->withSearchText($longText);
    }
    
    /**
     * Test director filtering
     */
    public function testWithDirector(): void
    {
        $result = $this->builder
            ->withDirector('Christopher Nolan')
            ->build();
        
        $this->assertStringContainsString('director LIKE ?', $result['sql']);
        $this->assertEquals(['%Christopher Nolan%'], $result['params']);
    }
    
    /**
     * Test sorting by valid field ascending
     */
    public function testWithSortingAscending(): void
    {
        $result = $this->builder
            ->withSorting('score', 'ASC')
            ->build();
        
        $this->assertStringContainsString('ORDER BY score ASC', $result['sql']);
    }
    
    /**
     * Test sorting by valid field descending
     */
    public function testWithSortingDescending(): void
    {
        $result = $this->builder
            ->withSorting('release_year', 'DESC')
            ->build();
        
        $this->assertStringContainsString('ORDER BY release_year DESC', $result['sql']);
    }
    
    /**
     * Test sorting with default direction
     */
    public function testWithSortingDefaultDirection(): void
    {
        $result = $this->builder
            ->withSorting('title')
            ->build();
        
        $this->assertStringContainsString('ORDER BY title ASC', $result['sql']);
    }
    
    /**
     * Test invalid sort field throws exception
     */
    public function testWithInvalidSortFieldThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field');
        
        $this->builder->withSorting('invalid_field', 'ASC');
    }
    
    /**
     * Test invalid sort direction throws exception
     */
    public function testWithInvalidSortDirectionThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort direction');
        
        $this->builder->withSorting('score', 'INVALID');
    }
    
    /**
     * Test limit functionality
     */
    public function testWithLimit(): void
    {
        $result = $this->builder
            ->withLimit(10)
            ->build();
        
        $this->assertStringContainsString('LIMIT 10', $result['sql']);
    }
    
    /**
     * Test invalid limit throws exception
     */
    public function testWithInvalidLimitThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be greater than 0');
        
        $this->builder->withLimit(0);
    }
    
    /**
     * Test excessive limit throws exception
     */
    public function testWithExcessiveLimitThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit cannot exceed 10000');
        
        $this->builder->withLimit(10001);
    }
    
    /**
     * Test offset functionality
     */
    public function testWithOffset(): void
    {
        $result = $this->builder
            ->withLimit(10)
            ->withOffset(20)
            ->build();
        
        $this->assertStringContainsString('LIMIT 10', $result['sql']);
        $this->assertStringContainsString('OFFSET 20', $result['sql']);
    }
    
    /**
     * Test negative offset throws exception
     */
    public function testWithNegativeOffsetThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset cannot be negative');
        
        $this->builder->withOffset(-1);
    }
    
    /**
     * Test complex query with multiple filters
     */
    public function testComplexQuery(): void
    {
        $result = $this->builder
            ->withCategories(['Action', 'Sci-Fi'])
            ->withScoreRange(8.0, 10.0)
            ->withYearRange(2000, 2024)
            ->withSearchText('space')
            ->withSorting('score', 'DESC')
            ->withLimit(20)
            ->build();
        
        // Verify SQL structure
        $this->assertStringContainsString('SELECT * FROM movies', $result['sql']);
        $this->assertStringContainsString('WHERE', $result['sql']);
        $this->assertStringContainsString('category IN (?,?)', $result['sql']);
        $this->assertStringContainsString('score >= ?', $result['sql']);
        $this->assertStringContainsString('score <= ?', $result['sql']);
        $this->assertStringContainsString('release_year >= ?', $result['sql']);
        $this->assertStringContainsString('release_year <= ?', $result['sql']);
        $this->assertStringContainsString('LIKE ?', $result['sql']);
        $this->assertStringContainsString('ORDER BY score DESC', $result['sql']);
        $this->assertStringContainsString('LIMIT 20', $result['sql']);
        
        // Verify parameters: 2 categories + 2 scores + 2 years + 4 search terms = 10
        $this->assertCount(10, $result['params']);
    }
    
    /**
     * Test buildWhereClause method
     */
    public function testBuildWhereClause(): void
    {
        $whereClause = $this->builder
            ->withCategories(['Action'])
            ->withScoreRange(8.0, null)
            ->buildWhereClause();
        
        $this->assertStringContainsString('category IN (?)', $whereClause);
        $this->assertStringContainsString('score >= ?', $whereClause);
        $this->assertStringContainsString('AND', $whereClause);
    }
    
    /**
     * Test buildWhereClause returns default when no filters
     */
    public function testBuildWhereClauseEmpty(): void
    {
        $whereClause = $this->builder->buildWhereClause();
        
        $this->assertEquals('1=1', $whereClause);
    }
    
    /**
     * Test getParams method
     */
    public function testGetParams(): void
    {
        $this->builder
            ->withCategories(['Action', 'Drama'])
            ->withScoreRange(7.5, 9.0);
        
        $params = $this->builder->getParams();
        
        $this->assertEquals(['Action', 'Drama', 7.5, 9.0], $params);
    }
    
    /**
     * Test reset method
     */
    public function testReset(): void
    {
        $this->builder
            ->withCategories(['Action'])
            ->withScoreRange(8.0, null)
            ->withSorting('score', 'DESC')
            ->withLimit(10);
        
        $this->builder->reset();
        
        $result = $this->builder->build();
        
        $this->assertEquals('SELECT * FROM movies', $result['sql']);
        $this->assertEmpty($result['params']);
    }
    
    /**
     * Test fluent interface returns self
     */
    public function testFluentInterface(): void
    {
        $result = $this->builder
            ->withCategories(['Action'])
            ->withScoreRange(8.0, 10.0)
            ->withYearRange(2000, 2024)
            ->withSorting('score', 'DESC');
        
        $this->assertInstanceOf(FilterBuilder::class, $result);
    }
    
    /**
     * Test clone method
     */
    public function testClone(): void
    {
        $this->builder
            ->withCategories(['Action'])
            ->withScoreRange(8.0, null);
        
        $clone = $this->builder->clone();
        
        // Modify clone
        $clone->withYearRange(2000, null);
        
        // Original should be unchanged
        $originalParams = $this->builder->getParams();
        $cloneParams = $clone->getParams();
        
        $this->assertCount(2, $originalParams); // Action, 8.0
        $this->assertCount(3, $cloneParams); // Action, 8.0, 2000
    }
    
    /**
     * Test debug method
     */
    public function testDebug(): void
    {
        $this->builder
            ->withCategories(['Action'])
            ->withScoreRange(8.0, null)
            ->withSorting('score', 'DESC')
            ->withLimit(10);
        
        $debug = $this->builder->debug();
        
        $this->assertIsArray($debug);
        $this->assertArrayHasKey('filters', $debug);
        $this->assertArrayHasKey('params', $debug);
        $this->assertArrayHasKey('orderBy', $debug);
        $this->assertArrayHasKey('limit', $debug);
        $this->assertArrayHasKey('offset', $debug);
        
        $this->assertCount(2, $debug['filters']);
        $this->assertEquals(['Action', 8.0], $debug['params']);
        $this->assertEquals('score DESC', $debug['orderBy']);
        $this->assertEquals(10, $debug['limit']);
    }
    
    /**
     * Test that method chaining works with all methods
     */
    public function testMethodChainingCompleteness(): void
    {
        $result = $this->builder
            ->withCategories(['Action', 'Sci-Fi'])
            ->withScoreRange(7.0, 9.5)
            ->withYearRange(1990, 2024)
            ->withRuntimeRange(90, 180)
            ->withSearchText('hero')
            ->withDirector('Nolan')
            ->withSorting('score', 'DESC')
            ->withLimit(50)
            ->withOffset(0)
            ->build();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('params', $result);
    }
}
