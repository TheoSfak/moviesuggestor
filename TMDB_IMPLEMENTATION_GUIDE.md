# 🚀 TMDB ONLINE CONVERSION - IMPLEMENTATION GUIDE

## ⚡ WHAT'S BEEN DONE FOR YOU

✅ **Database Migration Created** - `migrations/007_tmdb_integration.sql`
✅ **TMDBService Enhanced** - Advanced filtering with Greek categories
✅ **FavoritesRepository Refactored** - Now uses TMDB IDs
✅ **WatchLaterRepository Refactored** - Now uses TMDB IDs  
✅ **RatingRepositoryTMDB Created** - New TMDB-based version

## 🎯 YOUR IMPLEMENTATION STEPS

### Step 1: Run Database Migration (5 min)

```bash
cd c:\xampp\htdocs\moviesuggestor
mysql -u root moviesuggestor < migrations\007_tmdb_integration.sql
```

### Step 2: Update RatingRepository (2 min)

```bash
cd c:\Users\user\Desktop\moviesuggestor\src
copy /Y RatingRepositoryTMDB.php RatingRepository.php
```

Then change class name from `RatingRepositoryTMDB` to `RatingRepository`.

### Step 3: Refactor index.php - CRITICAL CHANGE (30 min)

**Replace MovieRepository with TMDBService:**

```php
// ADD at top with other use statements:
use MovieSuggestor\TMDBService;

// REMOVE these lines:
$movieRepo = new MovieRepository($database);
$allCategories = $movieRepo->getAllCategories();
$movies = $builder->execute($database->connect());

// ADD instead:
$tmdbService = new TMDBService();
$allCategories = array_values(TMDBService::getGenreMap());

// Build TMDB filters
$tmdbFilters = [];
if (!empty($categories)) {
    $tmdbFilters['categories'] = $categories;
}
if ($minScore > 0) {
    $tmdbFilters['vote_average_gte'] = $minScore;
}
if ($yearFrom) {
    $tmdbFilters['year_from'] = $yearFrom;
}
if ($yearTo) {
    $tmdbFilters['year_to'] = $yearTo;
}
if (!empty($searchText)) {
    $tmdbFilters['query'] = $searchText;
}
$tmdbFilters['page'] = $page;

// Get movies from TMDB
$tmdbResult = $tmdbService->discoverMovies($tmdbFilters);
$movies = $tmdbResult['results'] ?? [];
$totalPages = min($tmdbResult['total_pages'] ?? 1, 500);
$totalMovies = $tmdbResult['total_results'] ?? 0;

// Get user data with TMDB IDs
$userFavorites = array_flip($favoritesRepo->getFavoriteTmdbIds($userId));
$userWatchLater = array_flip($watchLaterRepo->getWatchLaterTmdbIds($userId));
$userRatings = $ratingRepo->getUserRatedTmdbIds($userId);
```

**Update HTML movie cards:**

```html
<!-- Change data-movie-id to data-tmdb-id -->
<div class="movie-card" 
     data-tmdb-id="<?= $movie['tmdb_id'] ?>"
     data-title="<?= htmlspecialchars($movie['title']) ?>"
     data-poster="<?= htmlspecialchars($movie['poster_url'] ?? '') ?>"
     data-year="<?= $movie['year'] ?? '' ?>"
     data-category="<?= htmlspecialchars($movie['category']) ?>">
    
    <!-- Update checks to use tmdb_id -->
    <button class="action-btn favorite-btn <?= isset($userFavorites[$movie['tmdb_id']]) ? 'active' : '' ?>" 
            onclick="toggleFavorite(<?= $movie['tmdb_id'] ?>, this)">
```

### Step 4: Update JavaScript Functions (15 min)

**toggleFavorite():**

```javascript
async function toggleFavorite(tmdbId, button) {
    const card = button.closest('.movie-card');
    const movieData = {
        title: card.dataset.title,
        poster_url: card.dataset.poster,
        release_year: parseInt(card.dataset.year) || null,
        category: card.dataset.category
    };
    
    const isActive = button.classList.contains('active');
    
    const response = await fetch('api/favorites.php', {
        method: isActive ? 'DELETE' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_id: USER_ID,
            tmdb_id: tmdbId,
            movie_data: movieData
        })
    });
    
    const data = await response.json();
    if (data.success) {
        button.classList.toggle('active');
        button.textContent = isActive ? '❤️ Favorite' : '❤️ Favorited';
        showMessage(isActive ? 'Removed from favorites' : 'Added to favorites!');
    }
}
```

**toggleWatchLater():** (Similar pattern)

```javascript
async function toggleWatchLater(tmdbId, button) {
    const card = button.closest('.movie-card');
    const movieData = {
        title: card.dataset.title,
        poster_url: card.dataset.poster,
        release_year: parseInt(card.dataset.year) || null,
        category: card.dataset.category
    };
    
    const isActive = button.classList.contains('active');
    
    const response = await fetch('api/watch-later.php', {
        method: isActive ? 'DELETE' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_id: USER_ID,
            tmdb_id: tmdbId,
            movie_data: movieData
        })
    });
    
    const data = await response.json();
    if (data.success) {
        button.classList.toggle('active');
        button.textContent = isActive ? '📌 Watch Later' : '📌 In List';
        showMessage(isActive ? 'Removed from watch later' : 'Added to watch later!');
    }
}
```

**rateMovie():**

```javascript
async function rateMovie(tmdbId, rating) {
    const card = document.querySelector(`[data-tmdb-id="${tmdbId}"]`);
    const movieData = {
        title: card.dataset.title,
        poster_url: card.dataset.poster,
        release_year: parseInt(card.dataset.year) || null,
        category: card.dataset.category
    };
    
    const response = await fetch('api/ratings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_id: USER_ID,
            tmdb_id: tmdbId,
            rating: rating,
            movie_data: movieData
        })
    });
    
    const data = await response.json();
    if (data.success) {
        // Update stars UI
        const starsContainer = document.querySelector(`.stars[data-movie-id="${tmdbId}"]`);
        // ... rest of stars update logic
        showMessage(`Rated ${rating}/10!`);
    }
}
```

### Step 5: Update api/favorites.php (10 min)

```php
// POST - Add favorite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    
    if (!validateRequired($input, ['user_id', 'tmdb_id'])) {
        sendResponse(null, 400, 'Missing required fields: user_id, tmdb_id');
    }
    
    $userId = (int)$input['user_id'];
    $tmdbId = (int)$input['tmdb_id'];
    $movieData = $input['movie_data'] ?? [];
    
    try {
        $result = $repo->addToFavorites($userId, $tmdbId, $movieData);
        
        if ($result) {
            sendResponse(['message' => 'Added to favorites']);
        } else {
            sendResponse(null, 500, 'Failed to add favorite');
        }
    } catch (Exception $e) {
        error_log('Favorites API error: ' . $e->getMessage());
        sendResponse(null, 500, $e->getMessage());
    }
}

// DELETE - Remove favorite
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = getJsonInput();
    
    if (!validateRequired($input, ['user_id', 'tmdb_id'])) {
        sendResponse(null, 400, 'Missing required fields');
    }
    
    $userId = (int)$input['user_id'];
    $tmdbId = (int)$input['tmdb_id'];
    
    try {
        $result = $repo->removeFromFavorites($userId, $tmdbId);
        
        if ($result) {
            sendResponse(['message' => 'Removed from favorites']);
        } else {
            sendResponse(null, 500, 'Failed to remove favorite');
        }
    } catch (Exception $e) {
        error_log('Favorites API error: ' . $e->getMessage());
        sendResponse(null, 500, $e->getMessage());
    }
}
```

### Step 6: Update api/watch-later.php (10 min)

Same pattern - replace `movie_id` with `tmdb_id`, add `movie_data`:

```php
// POST
$tmdbId = (int)$input['tmdb_id'];
$movieData = $input['movie_data'] ?? [];
$result = $repo->addToWatchLater($userId, $tmdbId, $movieData);

// DELETE
$tmdbId = (int)$input['tmdb_id'];
$result = $repo->removeFromWatchLater($userId, $tmdbId);

// PATCH (mark as watched)
$tmdbId = (int)$input['tmdb_id'];
$result = $repo->markAsWatched($userId, $tmdbId);
```

### Step 7: Update api/ratings.php (10 min)

```php
// POST - Add/update rating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    
    if (!validateRequired($input, ['user_id', 'tmdb_id', 'rating'])) {
        sendResponse(null, 400, 'Missing required fields: user_id, tmdb_id, rating');
    }
    
    $userId = (int)$input['user_id'];
    $tmdbId = (int)$input['tmdb_id'];
    $rating = (float)$input['rating'];
    $movieData = $input['movie_data'] ?? [];
    $review = $input['review'] ?? null;
    
    // Validate rating range
    if ($rating < 1.0 || $rating > 10.0) {
        sendResponse(null, 400, 'Rating must be between 1.0 and 10.0');
    }
    
    try {
        $result = $repo->addRating($userId, $tmdbId, $rating, $movieData, $review);
        
        if ($result) {
            sendResponse(['message' => 'Rating saved', 'rating' => $rating]);
        } else {
            sendResponse(null, 500, 'Failed to save rating');
        }
    } catch (Exception $e) {
        error_log('Ratings API error: ' . $e->getMessage());
        sendResponse(null, 500, $e->getMessage());
    }
}

// DELETE
$tmdbId = (int)$input['tmdb_id'];
$result = $repo->deleteRating($userId, $tmdbId);
```

### Step 8: Copy Files & Test (5 min)

```bash
# Copy to XAMPP
xcopy /E /Y c:\Users\user\Desktop\moviesuggestor\* c:\xampp\htdocs\moviesuggestor\

# Restart Apache
net stop Apache2.4
net start Apache2.4

# Open browser
start http://localhost/moviesuggestor/
```

## ✅ TESTING CHECKLIST

- [ ] Page loads with TMDB movies
- [ ] Category filter works
- [ ] Year range filter works
- [ ] Minimum score filter works
- [ ] Text search works
- [ ] Pagination works
- [ ] Add to favorites (check DB: `SELECT * FROM favorites`)
- [ ] Remove from favorites
- [ ] Add to watch later
- [ ] Remove from watch later
- [ ] Rate a movie (1-10 stars)
- [ ] No JavaScript errors in console
- [ ] No PHP errors in Apache logs

## 📊 CHECK DATABASE

```sql
USE moviesuggestor;

-- Verify migration
SHOW COLUMNS FROM favorites WHERE Field = 'tmdb_id';

-- Check data
SELECT user_id, tmdb_id, movie_title, category FROM favorites LIMIT 5;
SELECT user_id, tmdb_id, movie_title, category FROM watch_later LIMIT 5;
SELECT user_id, tmdb_id, rating, movie_title FROM ratings LIMIT 5;
```

## 🎉 BENEFITS

1. **800,000+ movies** instantly available
2. **Always fresh** data from TMDB
3. **Greek language** support
4. **Professional posters** and metadata
5. **No local database** maintenance
6. **Fast API** responses
7. **Smaller database** (only user data)

## ⏱️ ESTIMATED TIME

- Migration: 5 min
- RatingRepo: 2 min
- index.php: 30 min
- JavaScript: 15 min
- API endpoints: 30 min (3 files)
- Testing: 10 min

**Total: ~90 minutes**

---

**You're now running a modern, cloud-powered movie discovery app! 🎬🚀**
