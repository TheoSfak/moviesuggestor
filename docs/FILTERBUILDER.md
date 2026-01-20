# FilterBuilder - Advanced Query Building

## Overview

The `FilterBuilder` class provides a powerful, fluent interface for constructing complex SQL queries to filter movies. It ensures SQL injection safety through prepared statements and validates all user inputs.

## Features

✅ **Fluent Interface** - Chain methods for readable query construction  
✅ **SQL Injection Safe** - Uses prepared statements with parameter binding  
✅ **Input Validation** - Comprehensive validation for all inputs  
✅ **Multiple Filters** - Categories, scores, years, runtime, text search  
✅ **Flexible Sorting** - Sort by any column in ASC/DESC order  
✅ **Pagination Support** - Built-in LIMIT and OFFSET  
✅ **Production Ready** - Full PHPDoc, error handling, and testing  

---

## Quick Start

```php
require_once 'src/FilterBuilder.php';

// Create builder instance
$builder = new FilterBuilder();

// Build a simple query
$result = $builder
    ->withCategories(['Action', 'Sci-Fi'])
    ->withScoreRange(8.0, 10.0)
    ->withSorting('score', 'DESC')
    ->build();

// Execute with PDO
$pdo = new PDO('mysql:host=localhost;dbname=movie_db', 'user', 'pass');
$stmt = $pdo->prepare($result['sql']);
$stmt->execute($result['params']);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

---

## Method Reference

### Filter Methods

#### `withCategories(array $categories): self`

Filter movies by one or more categories (OR logic).

```php
// Single category
$builder->withCategories(['Action']);

// Multiple categories
$builder->withCategories(['Action', 'Sci-Fi', 'Drama']);
```

**Validation:**
- Array cannot be empty
- All values must be non-empty strings

**SQL Generated:** `category IN (?,?,?)`

---

#### `withScoreRange(?float $min, ?float $max): self`

Filter movies by score range (inclusive).

```php
// Minimum score only
$builder->withScoreRange(8.0, null);

// Maximum score only
$builder->withScoreRange(null, 9.5);

// Both minimum and maximum
$builder->withScoreRange(7.5, 9.5);
```

**Validation:**
- Scores must be between 0.0 and 10.0
- Min cannot be greater than max

**SQL Generated:** `score >= ? AND score <= ?`

---

#### `withYearRange(?int $start, ?int $end): self`

Filter movies by release year range (inclusive).

```php
// From year onwards
$builder->withYearRange(2000, null);

// Up to year
$builder->withYearRange(null, 2020);

// Specific range
$builder->withYearRange(2000, 2020);
```

**Validation:**
- Years must be between 1888 and 2100
- Start year cannot be greater than end year

**SQL Generated:** `release_year >= ? AND release_year <= ?`

---

#### `withRuntimeRange(?int $minMinutes, ?int $maxMinutes): self`

Filter movies by runtime in minutes (inclusive).

```php
// Movies between 90 and 150 minutes
$builder->withRuntimeRange(90, 150);

// Movies at least 120 minutes
$builder->withRuntimeRange(120, null);
```

**Validation:**
- Runtime must be greater than 0
- Min cannot be greater than max

**SQL Generated:** `runtime_minutes >= ? AND runtime_minutes <= ?`

---

#### `withSearchText(?string $text): self`

Search for movies by text in title, description, director, or actors.

```php
// Search across all text fields
$builder->withSearchText('space adventure');

// Case-insensitive partial matching
$builder->withSearchText('Nolan');
```

**Validation:**
- Text cannot exceed 500 characters
- Empty strings are ignored

**SQL Generated:** `(title LIKE ? OR description LIKE ? OR director LIKE ? OR actors LIKE ?)`

---

#### `withDirector(?string $director): self`

Filter movies by director name.

```php
// Find movies by specific director
$builder->withDirector('Christopher Nolan');
```

**Validation:**
- Director name cannot exceed 255 characters

**SQL Generated:** `director LIKE ?`

---

### Sorting and Pagination

#### `withSorting(string $field, string $direction = 'ASC'): self`

Set sorting for results.

```php
// Sort by score descending
$builder->withSorting('score', 'DESC');

// Sort by year ascending (default)
$builder->withSorting('release_year');

// Sort by title
$builder->withSorting('title', 'ASC');
```

**Valid Fields:**
- `title`, `category`, `score`
- `release_year`, `runtime_minutes`
- `imdb_rating`, `user_rating`, `votes_count`
- `created_at`, `updated_at`

**Valid Directions:** `ASC`, `DESC`

**SQL Generated:** `ORDER BY score DESC`

---

#### `withLimit(int $limit): self`

Limit the number of results.

```php
// Get top 20 results
$builder->withLimit(20);
```

**Validation:**
- Limit must be greater than 0
- Limit cannot exceed 10,000

**SQL Generated:** `LIMIT 20`

---

#### `withOffset(int $offset): self`

Skip a number of results (for pagination).

```php
// Skip first 40 results (page 3 with limit 20)
$builder->withOffset(40);
```

**Validation:**
- Offset cannot be negative

**SQL Generated:** `OFFSET 40`

---

### Query Building Methods

#### `build(): array`

Build the complete SQL query with parameters.

```php
$result = $builder->build();

// Returns:
// [
//     'sql' => 'SELECT * FROM movies WHERE...',
//     'params' => [...]
// ]

$stmt = $pdo->prepare($result['sql']);
$stmt->execute($result['params']);
```

---

#### `buildWhereClause(): string`

Get just the WHERE clause (without WHERE keyword).

```php
$whereClause = $builder->buildWhereClause();

// Returns: "category IN (?) AND score >= ?"
// Or: "1=1" if no filters
```

Useful for integrating with existing query builders.

---

#### `getParams(): array`

Get the prepared statement parameters.

```php
$params = $builder->getParams();

// Returns: ['Action', 'Sci-Fi', 8.0, 10.0]
```

---

### Execution Methods

#### `execute(PDO $pdo): array`

Build and execute the query, returning results.

```php
$pdo = new PDO('mysql:host=localhost;dbname=movie_db', 'user', 'pass');
$movies = $builder->execute($pdo);

// Returns array of movie records
foreach ($movies as $movie) {
    echo $movie['title'];
}
```

---

#### `count(PDO $pdo): int`

Get the count of matching records without fetching them.

```php
$total = $builder->count($pdo);

echo "Found $total movies matching criteria";
```

Useful for pagination (calculating total pages).

---

### Utility Methods

#### `reset(): self`

Clear all filters and settings to start fresh.

```php
$builder->reset();

// Builder is now in clean state
```

---

#### `clone(): self`

Create a copy of the builder with current state.

```php
$baseBuilder = (new FilterBuilder())
    ->withCategories(['Action'])
    ->withScoreRange(8.0, null);

// Create variations
$highScores = $baseBuilder->clone()->withScoreRange(9.0, null);
$recent = $baseBuilder->clone()->withYearRange(2020, null);
```

---

#### `debug(): array`

Get debug information about current filters.

```php
$info = $builder->debug();

// Returns:
// [
//     'filters' => ['category IN (?)', 'score >= ?'],
//     'params' => ['Action', 8.0],
//     'orderBy' => 'score DESC',
//     'limit' => 20,
//     'offset' => 0
// ]
```

---

## Usage Examples

### Example 1: Top Rated Action Movies

```php
$builder = new FilterBuilder();

$movies = $builder
    ->withCategories(['Action'])
    ->withScoreRange(8.0, 10.0)
    ->withSorting('score', 'DESC')
    ->withLimit(10)
    ->execute($pdo);
```

### Example 2: Recent Sci-Fi Films

```php
$builder = new FilterBuilder();

$movies = $builder
    ->withCategories(['Sci-Fi'])
    ->withYearRange(2020, 2024)
    ->withSorting('release_year', 'DESC')
    ->execute($pdo);
```

### Example 3: Search for Director

```php
$builder = new FilterBuilder();

$movies = $builder
    ->withDirector('Christopher Nolan')
    ->withSorting('release_year', 'DESC')
    ->execute($pdo);
```

### Example 4: Complex Search

```php
$builder = new FilterBuilder();

$movies = $builder
    ->withCategories(['Action', 'Sci-Fi', 'Drama'])
    ->withScoreRange(7.5, 10.0)
    ->withYearRange(2000, 2024)
    ->withRuntimeRange(90, 180)
    ->withSearchText('hero')
    ->withSorting('score', 'DESC')
    ->withLimit(50)
    ->execute($pdo);
```

### Example 5: Pagination

```php
$builder = new FilterBuilder();
$perPage = 20;
$page = 3; // Current page

// Get total count
$total = $builder
    ->withCategories(['Action'])
    ->withScoreRange(8.0, null)
    ->count($pdo);

$totalPages = ceil($total / $perPage);

// Get page results
$movies = $builder
    ->withLimit($perPage)
    ->withOffset(($page - 1) * $perPage)
    ->execute($pdo);

echo "Showing page $page of $totalPages";
```

### Example 6: User Search Interface

```php
function searchMovies($userInput, $pdo) {
    $builder = new FilterBuilder();
    
    // Apply user filters
    if (!empty($userInput['categories'])) {
        $builder->withCategories($userInput['categories']);
    }
    
    if (isset($userInput['min_score'])) {
        $builder->withScoreRange($userInput['min_score'], $userInput['max_score'] ?? null);
    }
    
    if (isset($userInput['year_from'])) {
        $builder->withYearRange($userInput['year_from'], $userInput['year_to'] ?? null);
    }
    
    if (!empty($userInput['search'])) {
        $builder->withSearchText($userInput['search']);
    }
    
    // Apply sorting
    $sortField = $userInput['sort'] ?? 'score';
    $sortDir = $userInput['order'] ?? 'DESC';
    $builder->withSorting($sortField, $sortDir);
    
    // Apply pagination
    $page = $userInput['page'] ?? 1;
    $perPage = 20;
    $builder->withLimit($perPage)->withOffset(($page - 1) * $perPage);
    
    return $builder->execute($pdo);
}

// Usage
$results = searchMovies($_GET, $pdo);
```

---

## Error Handling

All validation errors throw `InvalidArgumentException` with descriptive messages:

```php
try {
    $builder->withScoreRange(-1.0, 10.0);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // "Minimum score must be between 0.0 and 10.0"
}
```

Common validation errors:
- Empty categories array
- Invalid score range (< 0 or > 10)
- Invalid year range (< 1888 or > 2100)
- Invalid runtime (≤ 0)
- Search text too long (> 500 chars)
- Invalid sort field or direction
- Invalid limit or offset

---

## SQL Safety

The FilterBuilder uses **prepared statements** exclusively, preventing SQL injection:

```php
// User input
$userSearch = "'; DROP TABLE movies; --";

// Safe - parameterized
$builder->withSearchText($userSearch);
$result = $builder->build();

// SQL: (title LIKE ? OR description LIKE ? ...)
// Params: ["%'; DROP TABLE movies; --%", ...]
// No SQL injection possible!
```

All user inputs are:
1. ✅ Validated for type and range
2. ✅ Passed as parameters (never concatenated)
3. ✅ Properly escaped by PDO

---

## Performance Tips

1. **Use indexes** on filtered columns:
   ```sql
   CREATE INDEX idx_category ON movies(category);
   CREATE INDEX idx_score ON movies(score);
   CREATE INDEX idx_year ON movies(release_year);
   ```

2. **Limit results** to avoid fetching unnecessary data:
   ```php
   $builder->withLimit(100); // Don't fetch 10,000 rows
   ```

3. **Use `count()` for totals** instead of fetching all rows:
   ```php
   $total = $builder->count($pdo); // Fast
   // vs
   $all = $builder->execute($pdo);
   $total = count($all); // Slow
   ```

4. **Clone builders** instead of creating new ones:
   ```php
   $base = (new FilterBuilder())->withCategories(['Action']);
   $variant1 = $base->clone()->withScoreRange(8.0, null);
   $variant2 = $base->clone()->withYearRange(2020, null);
   ```

---

## Testing

Run the comprehensive test suite:

```bash
./vendor/bin/phpunit tests/FilterBuilderTest.php
```

Test coverage includes:
- ✅ All filter methods
- ✅ Input validation
- ✅ SQL generation
- ✅ Parameter binding
- ✅ Error handling
- ✅ Edge cases
- ✅ Complex queries

---

## Requirements

- **PHP:** ≥ 8.0
- **PDO:** MySQL driver
- **Database:** MySQL/MariaDB with Phase 2 migrations applied

---

## See Also

- [PHASE2_ARCHITECTURE.md](PHASE2_ARCHITECTURE.md) - Architecture overview
- [PHASE2_DATABASE_SPEC.md](PHASE2_DATABASE_SPEC.md) - Database schema
- [migrations/001_add_movie_metadata.sql](migrations/001_add_movie_metadata.sql) - Required migration

---

## Support

For issues or questions:
1. Check test suite for usage examples
2. Review inline PHPDoc comments
3. See example implementations above
4. Debug with `$builder->debug()` method
