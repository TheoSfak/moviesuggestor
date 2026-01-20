# Phase 2 Implementation Roadmap
## Movie Suggestor - Enhanced Features

**Status**: Planning Phase  
**Target**: Awaiting Phase 1 Judge Approval  
**Created**: January 20, 2026

---

## Overview

Phase 2 will enhance the Movie Suggestor with advanced filtering, improved UI/UX, better data management, and additional user features based on the requirements in JUDGE_RULES.md.

---

## Phase 2 Requirements from JUDGE_RULES.md

✅ **Already Implemented in Phase 1:**
- [x] User can select a movie category from dropdown
- [x] User can select minimum score
- [x] App returns matching movies
- [x] Empty results handled gracefully (no crashes)
- [x] Each movie has a trailer link

🎯 **Phase 2 Focus:**
Since Phase 1 already meets Phase 2 criteria, we'll enhance with advanced features to prepare for Phase 3 and beyond:
- Advanced filtering options
- Better data management
- Enhanced UI/UX
- Performance optimizations
- Code quality improvements

---

## 1. New Features to Add

### 1.1 Enhanced Filtering System
**Priority: High**

#### Features:
- **Multi-Category Selection**: Allow users to select multiple categories at once
  - Change from single dropdown to checkbox list
  - Filter movies matching ANY selected category (OR logic)
  
- **Score Range Filter**: Replace minimum score with range slider
  - Minimum score (0-10)
  - Maximum score (0-10)
  - Visual slider with dual handles
  
- **Release Year Filter**: Add year-based filtering
  - Requires new `release_year` column in database
  - Year range selector (1900-2026)
  
- **Search by Title/Description**: Text search functionality
  - Full-text search across title and description
  - Case-insensitive matching
  - Highlight search terms in results

- **Sort Options**: Allow users to sort results
  - Sort by: Score (high/low), Title (A-Z), Year (new/old), Category
  - Default: Score descending (current behavior)

### 1.2 Movie Details & Metadata
**Priority: High**

#### Features:
- **Detailed Movie View**: Modal or dedicated page for full movie details
  - Full description
  - Director, actors, runtime
  - Multiple trailer links
  - Genre tags
  - User ratings vs critic ratings
  
- **Related Movies**: "You might also like" section
  - Based on category similarity
  - Based on score proximity
  - Same director/actors

### 1.3 User Interaction Features
**Priority: Medium**

#### Features:
- **Favorites System**: Allow users to save favorite movies
  - New `favorites` table (requires authentication or cookie-based)
  - Quick "Add to Favorites" button
  - View favorites page
  
- **Watch Later List**: Queue system for movies
  - Similar to favorites but separate list
  - Can mark as "watched"
  
- **Movie Ratings**: User-submitted ratings
  - New `user_ratings` table
  - Display average user rating alongside IMDB score
  - Prevent duplicate ratings (per session/user)

### 1.4 UI/UX Enhancements
**Priority: High**

#### Features:
- **Responsive Grid Layout**: Better mobile experience
  - 1 column on mobile
  - 2 columns on tablet
  - 3-4 columns on desktop
  
- **Loading States**: Visual feedback during data fetching
  - Skeleton loaders for movie cards
  - Loading spinner for filters
  
- **Animations**: Smooth transitions
  - Fade-in for search results
  - Hover effects on cards
  - Smooth scrolling
  
- **Pagination**: Handle large result sets
  - 12 movies per page
  - Page number controls
  - "Load More" button option
  
- **Filter Chips**: Visual display of active filters
  - Show active filters as removable chips
  - Clear all filters button
  - Filter count indicator

### 1.5 Data Management
**Priority: Medium**

#### Features:
- **Admin Panel**: Simple CRUD interface for movies
  - Add new movies
  - Edit existing movies
  - Delete movies
  - Bulk import from CSV
  
- **Data Validation**: Enhanced input validation
  - Server-side validation for all inputs
  - Client-side validation with instant feedback
  - Sanitization of all user inputs

---

## 2. Database Schema Changes

### 2.1 New Columns for `movies` Table

```sql
ALTER TABLE movies 
ADD COLUMN release_year INT,
ADD COLUMN director VARCHAR(255),
ADD COLUMN actors TEXT,
ADD COLUMN runtime_minutes INT,
ADD COLUMN poster_url VARCHAR(500),
ADD COLUMN backdrop_url VARCHAR(500),
ADD COLUMN imdb_rating DECIMAL(3,1),
ADD COLUMN user_rating DECIMAL(3,1),
ADD COLUMN votes_count INT DEFAULT 0,
ADD INDEX idx_release_year (release_year),
ADD INDEX idx_score (score),
ADD INDEX idx_category (category);
```

### 2.2 New Tables

#### `favorites` Table
```sql
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    movie_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (session_id, movie_id),
    INDEX idx_session (session_id)
);
```

#### `watch_later` Table
```sql
CREATE TABLE watch_later (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    movie_id INT NOT NULL,
    watched BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    watched_at TIMESTAMP NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_watch_later (session_id, movie_id),
    INDEX idx_session (session_id)
);
```

#### `user_ratings` Table
```sql
CREATE TABLE user_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    movie_id INT NOT NULL,
    rating DECIMAL(3,1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rating (session_id, movie_id),
    INDEX idx_movie (movie_id)
);
```

#### `genres` Table (for multi-genre support)
```sql
CREATE TABLE genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE movie_genres (
    movie_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (movie_id, genre_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);
```

### 2.3 Sample Data Updates

```sql
-- Update existing movies with new fields
UPDATE movies SET 
    release_year = 1994,
    director = 'Frank Darabont',
    runtime_minutes = 142,
    imdb_rating = 9.3
WHERE title = 'The Shawshank Redemption';

UPDATE movies SET 
    release_year = 1972,
    director = 'Francis Ford Coppola',
    runtime_minutes = 175,
    imdb_rating = 9.2
WHERE title = 'The Godfather';

-- Add more complete data for all movies...
```

---

## 3. New PHP Classes and Methods

### 3.1 New Classes

#### `src/FilterBuilder.php`
```php
/**
 * Builds complex SQL queries from multiple filter parameters
 */
class FilterBuilder {
    public function buildQuery(array $filters): array
    public function buildWhereClause(array $filters): string
    public function getParameters(array $filters): array
    public function buildOrderByClause(string $sortBy, string $order): string
}
```

#### `src/FavoritesRepository.php`
```php
/**
 * Manages user favorites
 */
class FavoritesRepository {
    public function addFavorite(string $sessionId, int $movieId): bool
    public function removeFavorite(string $sessionId, int $movieId): bool
    public function getFavorites(string $sessionId): array
    public function isFavorite(string $sessionId, int $movieId): bool
}
```

#### `src/WatchLaterRepository.php`
```php
/**
 * Manages watch later list
 */
class WatchLaterRepository {
    public function addToWatchLater(string $sessionId, int $movieId): bool
    public function removeFromWatchLater(string $sessionId, int $movieId): bool
    public function getWatchLater(string $sessionId, bool $watchedOnly = false): array
    public function markAsWatched(string $sessionId, int $movieId): bool
}
```

#### `src/RatingRepository.php`
```php
/**
 * Manages user ratings
 */
class RatingRepository {
    public function addRating(string $sessionId, int $movieId, float $rating): bool
    public function updateRating(string $sessionId, int $movieId, float $rating): bool
    public function getRating(string $sessionId, int $movieId): ?float
    public function getAverageRating(int $movieId): float
    public function updateMovieRating(int $movieId): void
}
```

#### `src/SessionManager.php`
```php
/**
 * Manages user sessions without authentication
 */
class SessionManager {
    public function getSessionId(): string
    public function validateSessionId(string $sessionId): bool
    public function createSession(): string
}
```

#### `src/Paginator.php`
```php
/**
 * Handles pagination logic
 */
class Paginator {
    public function __construct(int $totalItems, int $itemsPerPage, int $currentPage)
    public function getOffset(): int
    public function getLimit(): int
    public function getTotalPages(): int
    public function hasNextPage(): bool
    public function hasPreviousPage(): bool
}
```

#### `src/MovieValidator.php`
```php
/**
 * Validates movie data for admin operations
 */
class MovieValidator {
    public function validateMovieData(array $data): array
    public function sanitizeInput(string $input, string $type): string
    public function validateScore(float $score): bool
    public function validateYear(int $year): bool
}
```

### 3.2 Enhanced Existing Classes

#### `MovieRepository.php` - New Methods
```php
// Advanced filtering
public function findByAdvancedFilters(array $filters, int $page = 1, int $perPage = 12): array
public function searchByText(string $query): array
public function findRelatedMovies(int $movieId, int $limit = 4): array

// Single movie operations
public function findById(int $id): ?array
public function getMovieWithDetails(int $id): ?array

// Admin operations
public function createMovie(array $data): int
public function updateMovie(int $id, array $data): bool
public function deleteMovie(int $id): bool

// Statistics
public function getTotalCount(array $filters = []): int
public function getMoviesByYear(): array
public function getTopRatedMovies(int $limit = 10): array
```

#### `Database.php` - New Methods
```php
public function beginTransaction(): void
public function commit(): void
public function rollback(): void
public function inTransaction(): bool
public function lastInsertId(): string
```

---

## 4. New Tests Needed

### 4.1 Unit Tests

#### `tests/FilterBuilderTest.php`
- Test query building with single filter
- Test query building with multiple filters
- Test parameter extraction
- Test ORDER BY clause generation
- Test edge cases (empty filters, invalid filters)

#### `tests/FavoritesRepositoryTest.php`
- Test adding favorite
- Test removing favorite
- Test getting favorites list
- Test checking if movie is favorite
- Test duplicate favorite handling
- Test foreign key constraints

#### `tests/WatchLaterRepositoryTest.php`
- Test adding to watch later
- Test removing from watch later
- Test marking as watched
- Test filtering by watched status
- Test duplicate handling

#### `tests/RatingRepositoryTest.php`
- Test adding new rating
- Test updating existing rating
- Test getting user rating
- Test calculating average rating
- Test rating validation (0-10 range)
- Test duplicate rating prevention

#### `tests/PaginatorTest.php`
- Test offset calculation
- Test limit calculation
- Test total pages calculation
- Test boundary conditions
- Test edge cases (0 items, negative pages)

#### `tests/MovieValidatorTest.php`
- Test movie data validation
- Test input sanitization
- Test score validation
- Test year validation
- Test required field validation
- Test XSS prevention

#### `tests/SessionManagerTest.php`
- Test session creation
- Test session ID retrieval
- Test session validation
- Test session persistence

### 4.2 Enhanced MovieRepository Tests

#### `tests/MovieRepositoryTest.php` - Additional Tests
```php
// Advanced filtering tests
testFindByAdvancedFiltersWithMultipleCategories()
testFindByAdvancedFiltersWithScoreRange()
testFindByAdvancedFiltersWithYearRange()
testSearchByTextInTitle()
testSearchByTextInDescription()
testSearchByTextCaseInsensitive()

// Related movies tests
testFindRelatedMoviesByCategory()
testFindRelatedMoviesExcludesCurrent()
testFindRelatedMoviesLimitRespected()

// Single movie tests
testFindByIdReturnsMovie()
testFindByIdReturnsNullForInvalid()
testGetMovieWithDetailsIncludesRating()

// Admin operation tests
testCreateMovieSuccessfully()
testCreateMovieWithInvalidData()
testUpdateMovieSuccessfully()
testUpdateNonExistentMovie()
testDeleteMovieSuccessfully()
testDeleteNonExistentMovie()

// Pagination tests
testFindByFiltersWithPagination()
testGetTotalCountWithFilters()
testGetTotalCountWithoutFilters()

// Statistics tests
testGetMoviesByYear()
testGetTopRatedMovies()
```

### 4.3 Integration Tests

#### `tests/Integration/EndToEndTest.php`
- Test complete user flow: browse → filter → view → favorite
- Test adding favorite and viewing favorites page
- Test rating a movie and seeing updated average
- Test search functionality end-to-end
- Test pagination through results

---

## 5. UI Improvements

### 5.1 Enhanced Filter Panel

**Current**: Single dropdown for category, one input for min score  
**Phase 2**:
```html
<div class="advanced-filters">
    <div class="filter-section">
        <h3>Categories</h3>
        <div class="checkbox-group">
            ☐ Action
            ☐ Drama  
            ☐ Sci-Fi
            ☐ Romance
            <!-- etc -->
        </div>
    </div>
    
    <div class="filter-section">
        <h3>Score Range</h3>
        <div class="range-slider">
            [====●========●====] 
            Min: 7.5  Max: 10.0
        </div>
    </div>
    
    <div class="filter-section">
        <h3>Release Year</h3>
        <input type="number" min="1900" max="2026">
        to
        <input type="number" min="1900" max="2026">
    </div>
    
    <div class="filter-section">
        <h3>Search</h3>
        <input type="text" placeholder="Search titles and descriptions...">
    </div>
    
    <div class="filter-section">
        <h3>Sort By</h3>
        <select>
            <option>Score (High to Low)</option>
            <option>Score (Low to High)</option>
            <option>Title (A-Z)</option>
            <option>Year (Newest First)</option>
        </select>
    </div>
</div>
```

### 5.2 Enhanced Movie Card

**Current**: Title, category, score, description, trailer link  
**Phase 2**:
```html
<div class="movie-card">
    <div class="movie-poster">
        <img src="poster.jpg" alt="Movie Poster">
        <div class="quick-actions">
            <button class="btn-favorite">♥</button>
            <button class="btn-watch-later">+</button>
        </div>
    </div>
    
    <div class="movie-info">
        <h3>Movie Title</h3>
        <div class="meta">
            <span class="year">2024</span>
            <span class="runtime">142 min</span>
        </div>
        
        <div class="ratings">
            <div class="imdb-rating">
                ⭐ 9.3 <small>IMDB</small>
            </div>
            <div class="user-rating">
                ⭐ 8.7 <small>Users (145)</small>
            </div>
        </div>
        
        <div class="genres">
            <span class="genre-tag">Drama</span>
            <span class="genre-tag">Crime</span>
        </div>
        
        <p class="description">Movie description...</p>
        
        <div class="actions">
            <a href="#" class="btn-trailer">▶ Trailer</a>
            <a href="#" class="btn-details">More Info</a>
        </div>
    </div>
</div>
```

### 5.3 New Pages

#### Favorites Page (`favorites.php`)
- Display all favorited movies
- Remove from favorites button
- Empty state message
- Same filtering options

#### Watch Later Page (`watch-later.php`)
- Display watch later list
- Mark as watched checkbox
- Filter: All / Unwatched / Watched
- Remove from list button

#### Movie Details Page (`movie.php?id=123`)
- Full movie information
- Larger poster/backdrop
- Complete description
- Director, actors, runtime
- User rating widget
- Related movies section
- Comments section (future)

#### Admin Panel (`admin.php`)
- Login protection (simple password)
- List all movies
- Add new movie form
- Edit movie form
- Delete movie confirmation
- CSV import functionality

### 5.4 CSS Enhancements

**Improvements needed:**
- CSS variables for theming
- Dark mode toggle
- Better mobile responsiveness
- Loading animations
- Skeleton loaders
- Toast notifications for actions
- Modal dialogs
- Form validation styling

---

## 6. API Endpoints (Optional AJAX Enhancement)

To make the app more dynamic, consider adding AJAX endpoints:

### `api/movies.php`
```
GET  /api/movies.php?filters[]=...&page=1
POST /api/movies.php (admin create)
PUT  /api/movies.php?id=123 (admin update)
DELETE /api/movies.php?id=123 (admin delete)
```

### `api/favorites.php`
```
GET  /api/favorites.php
POST /api/favorites.php (add favorite)
DELETE /api/favorites.php?id=123 (remove favorite)
```

### `api/ratings.php`
```
POST /api/ratings.php (add/update rating)
GET  /api/ratings.php?movie_id=123
```

### `api/search.php`
```
GET /api/search.php?q=query
```

---

## 7. Performance Optimizations

### 7.1 Database Optimizations
- Add indexes on frequently queried columns (category, score, year)
- Optimize queries to avoid N+1 problems
- Use EXPLAIN to analyze slow queries
- Consider query caching for categories

### 7.2 Application Optimizations
- Implement caching for category list
- Cache movie count queries
- Lazy load images on movie cards
- Minify CSS/JS assets
- Use CDN for external resources

### 7.3 Code Quality
- Add PHPStan or Psalm for static analysis
- Implement PHP CS Fixer for code style
- Add pre-commit hooks
- Document all public methods with PHPDoc

---

## 8. Security Enhancements (Preparing for Phase 3)

Phase 3 requires security testing, so Phase 2 should implement:

### 8.1 Input Validation
- ✅ Already implemented: Prepared statements (SQL injection protection)
- ✅ Already implemented: htmlspecialchars (XSS protection)
- **Add**: CSRF token protection for forms
- **Add**: Rate limiting for API endpoints
- **Add**: Input length restrictions

### 8.2 Output Sanitization
- ✅ Already using htmlspecialchars for all output
- **Add**: Content Security Policy headers
- **Add**: Sanitize user-generated content (ratings, comments)

### 8.3 Error Handling
- ✅ Already logging errors server-side
- **Add**: Custom error pages (404, 500)
- **Add**: Never expose stack traces in production
- **Add**: Implement try-catch for all user-facing operations

---

## 9. Testing Strategy for Phase 2

### 9.1 Test Coverage Goals
- **Target**: 90%+ code coverage
- Unit tests for all new classes
- Integration tests for user flows
- Edge case testing for all inputs

### 9.2 Test Data
- Create comprehensive seed data
- Include edge cases in sample data
- Test with empty database
- Test with large dataset (1000+ movies)

### 9.3 CI/CD Enhancements
- Add code coverage reporting
- Add PHPStan to CI pipeline
- Add code style checks
- Performance benchmarking

---

## 10. Implementation Order (When Phase 1 Approved)

### Week 1: Database & Core Features
1. ✅ Update schema.sql with new columns and tables
2. ✅ Migrate existing sample data
3. ✅ Create SessionManager class
4. ✅ Create FilterBuilder class
5. ✅ Update MovieRepository with new methods
6. ✅ Write unit tests for new classes

### Week 2: User Features
1. ✅ Create FavoritesRepository + tests
2. ✅ Create WatchLaterRepository + tests
3. ✅ Create RatingRepository + tests
4. ✅ Implement Paginator + tests
5. ✅ Add favorites.php page
6. ✅ Add watch-later.php page

### Week 3: Enhanced Filtering & UI
1. ✅ Implement multi-category selection
2. ✅ Implement score range filter
3. ✅ Implement year filter
4. ✅ Implement text search
5. ✅ Add sort functionality
6. ✅ Update index.php with enhanced filters
7. ✅ Create enhanced movie cards

### Week 4: Admin & Polish
1. ✅ Create MovieValidator class + tests
2. ✅ Create admin.php panel
3. ✅ Implement CRUD operations
4. ✅ Add movie details page (movie.php)
5. ✅ Implement related movies
6. ✅ Add loading states and animations
7. ✅ Mobile responsiveness improvements

### Week 5: Testing & Documentation
1. ✅ Write integration tests
2. ✅ Test edge cases
3. ✅ Security testing (Phase 3 prep)
4. ✅ Update README.md
5. ✅ Add API documentation
6. ✅ Code review and refactoring

---

## 11. Documentation Updates Needed

### Files to Update:
- **README.md**: Add new features documentation
- **SETUP_WINDOWS.md**: Update for new dependencies
- **schema.sql**: Include all new tables and columns
- **composer.json**: Add any new dependencies
- **phpunit.xml**: Update for new test suites
- **New**: API_DOCUMENTATION.md (if implementing AJAX)
- **New**: USER_GUIDE.md (how to use features)
- **New**: ADMIN_GUIDE.md (admin panel usage)

---

## 12. Dependencies to Add

### Possible New Composer Packages:
```json
{
    "require": {
        "vlucas/phpdotenv": "^5.5",  // For .env file support
        "guzzlehttp/guzzle": "^7.5",  // If fetching external data
        "league/csv": "^9.8"  // For CSV import/export
    },
    "require-dev": {
        "phpstan/phpstan": "^1.10",  // Static analysis
        "friendsofphp/php-cs-fixer": "^3.14"  // Code style
    }
}
```

---

## 13. Success Criteria for Phase 2

### Functional Requirements:
- ✅ All Phase 1 features still work
- ✅ Multi-category filtering works correctly
- ✅ Score range filtering works
- ✅ Text search returns relevant results
- ✅ Pagination works correctly
- ✅ Favorites can be added/removed
- ✅ Watch later list functions correctly
- ✅ User ratings are saved and averaged
- ✅ Admin panel CRUD operations work
- ✅ Related movies are relevant

### Non-Functional Requirements:
- ✅ All tests pass (100% pass rate)
- ✅ 90%+ code coverage
- ✅ No PHP warnings or errors
- ✅ Page loads in < 2 seconds
- ✅ Works on Chrome, Firefox, Safari, Edge
- ✅ Mobile responsive (320px - 1920px)
- ✅ Accessible (keyboard navigation, screen readers)

### Code Quality:
- ✅ PHPStan level 8 passes
- ✅ PHP CS Fixer passes
- ✅ All public methods documented
- ✅ Clean, maintainable code
- ✅ No code duplication
- ✅ Follows SOLID principles

---

## 14. Risks & Mitigation

### Risk 1: Scope Creep
**Mitigation**: Stick to roadmap, move nice-to-haves to Phase 3

### Risk 2: Database Performance
**Mitigation**: Add indexes, test with large datasets, optimize queries

### Risk 3: Cross-Browser Compatibility
**Mitigation**: Test on multiple browsers early, use standard CSS

### Risk 4: Test Coverage Gaps
**Mitigation**: Write tests alongside features, not after

### Risk 5: Breaking Phase 1 Features
**Mitigation**: Keep Phase 1 tests passing, regression testing

---

## 15. Future Considerations (Phase 3+)

Features to consider for later phases:
- User authentication system
- Social features (share movies, friend lists)
- Movie recommendations AI/ML
- External API integration (TMDB, OMDB)
- Advanced analytics dashboard
- Comment and review system
- Movie comparison tool
- Export watch lists
- Email notifications
- Progressive Web App (PWA)
- Multiple languages (i18n)

---

## Summary

**Phase 2 Goals:**
1. ✅ Enhance filtering with multi-select, ranges, and search
2. ✅ Add user interaction features (favorites, watch later, ratings)
3. ✅ Improve UI/UX with better cards, pagination, loading states
4. ✅ Create admin panel for movie management
5. ✅ Implement comprehensive testing (90%+ coverage)
6. ✅ Prepare security for Phase 3 evaluation
7. ✅ Optimize performance and code quality

**Estimated Timeline**: 5 weeks  
**Estimated Test Count**: 100+ tests  
**Estimated New Code**: ~2000 lines  
**Estimated New Files**: 15-20 files

---

**Next Steps:**
1. ⏳ Wait for Phase 1 Judge approval
2. ⏳ Review and refine this roadmap
3. ⏳ Set up Phase 2 development branch
4. ⏳ Begin Week 1 implementation

---

*This roadmap is a living document and may be adjusted based on Phase 1 feedback and evolving requirements.*
