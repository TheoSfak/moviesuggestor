<?php
/**
 * FilterBuilder - Advanced Movie Query Builder
 * 
 * Provides a fluent interface for constructing complex SQL queries
 * with multiple filtering criteria. Ensures SQL injection safety
 * through prepared statements and validates all user inputs.
 * 
 * @package MovieSuggestor
 * @author Movie Suggestor Team
 * @version 2.0.0
 */

/**
 * Class FilterBuilder
 * 
 * Builds complex movie filter queries using a fluent interface.
 * Supports multiple categories, score ranges, year ranges, text search,
 * runtime filters, and custom sorting with full input validation.
 * 
 * Example usage:
 * ```php
 * $builder = new FilterBuilder();
 * $result = $builder
 *     ->withCategories(['Action', 'Sci-Fi'])
 *     ->withScoreRange(8.0, 10.0)
 *     ->withYearRange(2000, 2024)
 *     ->withSearchText('space')
 *     ->withSorting('score', 'DESC')
 *     ->build();
 * 
 * $sql = $result['sql'];
 * $params = $result['params'];
 * 
 * // Or execute directly
 * $movies = $builder->execute($pdo);
 * ```
 */
class FilterBuilder
{
    /**
     * @var array<string> WHERE clause conditions
     */
    private array $filters = [];
    
    /**
     * @var array<mixed> Prepared statement parameters
     */
    private array $params = [];
    
    /**
     * @var string|null ORDER BY clause
     */
    private ?string $orderBy = null;
    
    /**
     * @var int|null LIMIT clause
     */
    private ?int $limit = null;
    
    /**
     * @var int|null OFFSET clause
     */
    private ?int $offset = null;
    
    /**
     * @var array<string> Valid sortable columns
     */
    private const VALID_SORT_FIELDS = [
        'title',
        'category',
        'score',
        'release_year',
        'runtime_minutes',
        'imdb_rating',
        'user_rating',
        'votes_count',
        'created_at',
        'updated_at'
    ];
    
    /**
     * @var array<string> Valid sort directions
     */
    private const VALID_SORT_DIRECTIONS = ['ASC', 'DESC'];
    
    /**
     * Filter by multiple categories using OR logic
     * 
     * @param array<string> $categories Array of category names to filter by
     * @return self For method chaining
     * @throws InvalidArgumentException If categories array is empty or contains invalid values
     */
    public function withCategories(array $categories): self
    {
        if (empty($categories)) {
            throw new InvalidArgumentException('Categories array cannot be empty');
        }
        
        // Validate all categories are non-empty strings
        foreach ($categories as $category) {
            if (!is_string($category) || trim($category) === '') {
                throw new InvalidArgumentException('All categories must be non-empty strings');
            }
        }
        
        // Build placeholders for IN clause
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $this->filters[] = "category IN ($placeholders)";
        $this->params = array_merge($this->params, $categories);
        
        return $this;
    }
    
    /**
     * Filter by score range (inclusive)
     * 
     * @param float|null $min Minimum score (0.0-10.0)
     * @param float|null $max Maximum score (0.0-10.0)
     * @return self For method chaining
     * @throws InvalidArgumentException If score values are invalid or min > max
     */
    public function withScoreRange(?float $min = null, ?float $max = null): self
    {
        if ($min !== null) {
            if ($min < 0.0 || $min > 10.0) {
                throw new InvalidArgumentException('Minimum score must be between 0.0 and 10.0');
            }
            $this->filters[] = "score >= ?";
            $this->params[] = $min;
        }
        
        if ($max !== null) {
            if ($max < 0.0 || $max > 10.0) {
                throw new InvalidArgumentException('Maximum score must be between 0.0 and 10.0');
            }
            $this->filters[] = "score <= ?";
            $this->params[] = $max;
        }
        
        if ($min !== null && $max !== null && $min > $max) {
            throw new InvalidArgumentException('Minimum score cannot be greater than maximum score');
        }
        
        return $this;
    }
    
    /**
     * Filter by release year range (inclusive)
     * 
     * @param int|null $start Starting year (1888-2100)
     * @param int|null $end Ending year (1888-2100)
     * @return self For method chaining
     * @throws InvalidArgumentException If year values are invalid or start > end
     */
    public function withYearRange(?int $start = null, ?int $end = null): self
    {
        if ($start !== null) {
            if ($start < 1888 || $start > 2100) {
                throw new InvalidArgumentException('Start year must be between 1888 and 2100');
            }
            $this->filters[] = "release_year >= ?";
            $this->params[] = $start;
        }
        
        if ($end !== null) {
            if ($end < 1888 || $end > 2100) {
                throw new InvalidArgumentException('End year must be between 1888 and 2100');
            }
            $this->filters[] = "release_year <= ?";
            $this->params[] = $end;
        }
        
        if ($start !== null && $end !== null && $start > $end) {
            throw new InvalidArgumentException('Start year cannot be greater than end year');
        }
        
        return $this;
    }
    
    /**
     * Filter by runtime range in minutes (inclusive)
     * 
     * @param int|null $minMinutes Minimum runtime in minutes
     * @param int|null $maxMinutes Maximum runtime in minutes
     * @return self For method chaining
     * @throws InvalidArgumentException If runtime values are invalid or min > max
     */
    public function withRuntimeRange(?int $minMinutes = null, ?int $maxMinutes = null): self
    {
        if ($minMinutes !== null) {
            if ($minMinutes <= 0) {
                throw new InvalidArgumentException('Minimum runtime must be greater than 0');
            }
            $this->filters[] = "runtime_minutes >= ?";
            $this->params[] = $minMinutes;
        }
        
        if ($maxMinutes !== null) {
            if ($maxMinutes <= 0) {
                throw new InvalidArgumentException('Maximum runtime must be greater than 0');
            }
            $this->filters[] = "runtime_minutes <= ?";
            $this->params[] = $maxMinutes;
        }
        
        if ($minMinutes !== null && $maxMinutes !== null && $minMinutes > $maxMinutes) {
            throw new InvalidArgumentException('Minimum runtime cannot be greater than maximum runtime');
        }
        
        return $this;
    }
    
    /**
     * Search by text in title, description, director, or actors
     * 
     * Performs a case-insensitive LIKE search across multiple fields.
     * 
     * @param string|null $text Search term
     * @return self For method chaining
     * @throws InvalidArgumentException If search text exceeds reasonable length
     */
    public function withSearchText(?string $text = null): self
    {
        if ($text !== null && $text !== '') {
            // Sanitize and validate search text
            $text = trim($text);
            
            if (strlen($text) > 500) {
                throw new InvalidArgumentException('Search text cannot exceed 500 characters');
            }
            
            // Search across multiple fields
            $this->filters[] = "(title LIKE ? OR description LIKE ? OR director LIKE ? OR actors LIKE ?)";
            $searchTerm = "%$text%";
            $this->params[] = $searchTerm;
            $this->params[] = $searchTerm;
            $this->params[] = $searchTerm;
            $this->params[] = $searchTerm;
        }
        
        return $this;
    }
    
    /**
     * Filter by director name
     * 
     * @param string|null $director Director name to search for
     * @return self For method chaining
     * @throws InvalidArgumentException If director name is invalid
     */
    public function withDirector(?string $director = null): self
    {
        if ($director !== null && $director !== '') {
            $director = trim($director);
            
            if (strlen($director) > 255) {
                throw new InvalidArgumentException('Director name cannot exceed 255 characters');
            }
            
            $this->filters[] = "director LIKE ?";
            $this->params[] = "%$director%";
        }
        
        return $this;
    }
    
    /**
     * Set sorting for results
     * 
     * @param string $field Column name to sort by
     * @param string $direction Sort direction ('ASC' or 'DESC')
     * @return self For method chaining
     * @throws InvalidArgumentException If field or direction is invalid
     */
    public function withSorting(string $field, string $direction = 'ASC'): self
    {
        $field = strtolower(trim($field));
        $direction = strtoupper(trim($direction));
        
        if (!in_array($field, self::VALID_SORT_FIELDS, true)) {
            throw new InvalidArgumentException(
                "Invalid sort field: $field. Valid fields: " . implode(', ', self::VALID_SORT_FIELDS)
            );
        }
        
        if (!in_array($direction, self::VALID_SORT_DIRECTIONS, true)) {
            throw new InvalidArgumentException(
                "Invalid sort direction: $direction. Valid directions: ASC, DESC"
            );
        }
        
        $this->orderBy = "$field $direction";
        
        return $this;
    }
    
    /**
     * Set limit for results
     * 
     * @param int $limit Maximum number of results to return
     * @return self For method chaining
     * @throws InvalidArgumentException If limit is invalid
     */
    public function withLimit(int $limit): self
    {
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit must be greater than 0');
        }
        
        if ($limit > 10000) {
            throw new InvalidArgumentException('Limit cannot exceed 10000');
        }
        
        $this->limit = $limit;
        
        return $this;
    }
    
    /**
     * Set offset for results (for pagination)
     * 
     * @param int $offset Number of results to skip
     * @return self For method chaining
     * @throws InvalidArgumentException If offset is invalid
     */
    public function withOffset(int $offset): self
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset cannot be negative');
        }
        
        $this->offset = $offset;
        
        return $this;
    }
    
    /**
     * Build the complete SQL query with parameters
     * 
     * Returns an array containing the SQL query string and prepared statement parameters.
     * 
     * @return array{sql: string, params: array<mixed>} Query data
     * @example
     * ```php
     * $result = $builder->build();
     * $stmt = $pdo->prepare($result['sql']);
     * $stmt->execute($result['params']);
     * ```
     */
    public function build(): array
    {
        // Start with base query
        $sql = "SELECT * FROM movies";
        
        // Add WHERE clause if filters exist
        if (!empty($this->filters)) {
            $whereClause = implode(' AND ', $this->filters);
            $sql .= " WHERE $whereClause";
        }
        
        // Add ORDER BY clause
        if ($this->orderBy !== null) {
            $sql .= " ORDER BY {$this->orderBy}";
        }
        
        // Add LIMIT clause
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        // Add OFFSET clause
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return [
            'sql' => $sql,
            'params' => $this->params
        ];
    }
    
    /**
     * Build WHERE clause only (without WHERE keyword)
     * 
     * Useful for integration with existing query builders.
     * 
     * @return string WHERE clause conditions (without WHERE keyword)
     */
    public function buildWhereClause(): string
    {
        return empty($this->filters) ? '1=1' : implode(' AND ', $this->filters);
    }
    
    /**
     * Get query parameters for prepared statement
     * 
     * @return array<mixed> Array of parameter values
     */
    public function getParams(): array
    {
        return $this->params;
    }
    
    /**
     * Execute the query and return results
     * 
     * @param PDO $pdo Database connection
     * @return array<array<string,mixed>> Array of movie records
     * @throws PDOException If query execution fails
     */
    public function execute(PDO $pdo): array
    {
        $result = $this->build();
        
        $stmt = $pdo->prepare($result['sql']);
        $stmt->execute($result['params']);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get count of matching records without fetching them
     * 
     * @param PDO $pdo Database connection
     * @return int Number of matching records
     * @throws PDOException If query execution fails
     */
    public function count(PDO $pdo): int
    {
        // Build count query (ignore ORDER BY, LIMIT, OFFSET)
        $sql = "SELECT COUNT(*) as count FROM movies";
        
        if (!empty($this->filters)) {
            $whereClause = implode(' AND ', $this->filters);
            $sql .= " WHERE $whereClause";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($this->params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }
    
    /**
     * Reset builder state to start fresh
     * 
     * Clears all filters, parameters, sorting, and pagination settings.
     * 
     * @return self For method chaining
     */
    public function reset(): self
    {
        $this->filters = [];
        $this->params = [];
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        
        return $this;
    }
    
    /**
     * Clone the current builder state
     * 
     * Useful for creating variations of a query without modifying the original.
     * 
     * @return self New FilterBuilder instance with copied state
     */
    public function clone(): self
    {
        $clone = new self();
        $clone->filters = $this->filters;
        $clone->params = $this->params;
        $clone->orderBy = $this->orderBy;
        $clone->limit = $this->limit;
        $clone->offset = $this->offset;
        
        return $clone;
    }
    
    /**
     * Get debug information about current filters
     * 
     * @return array{filters: array<string>, params: array<mixed>, orderBy: string|null, limit: int|null, offset: int|null}
     */
    public function debug(): array
    {
        return [
            'filters' => $this->filters,
            'params' => $this->params,
            'orderBy' => $this->orderBy,
            'limit' => $this->limit,
            'offset' => $this->offset
        ];
    }
}
