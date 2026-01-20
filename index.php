<?php

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Start session for user simulation
session_start();

// Simulate user login (in production, this would be proper authentication)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default user for demo
    $_SESSION['username'] = 'Demo User';
}

require_once __DIR__ . '/vendor/autoload.php';

use MovieSuggestor\Database;
use MovieSuggestor\MovieRepository;
use MovieSuggestor\FavoritesRepository;
use MovieSuggestor\WatchLaterRepository;
use MovieSuggestor\RatingRepository;

// Get filter parameters (Phase 1 - backward compatible)
$selectedCategory = $_GET['category'] ?? '';
$minScore = isset($_GET['min_score']) ? (float)$_GET['min_score'] : 0.0;

// Phase 2 - Advanced filtering parameters
$categories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
$searchText = $_GET['search'] ?? '';
$yearFrom = isset($_GET['year_from']) ? (int)$_GET['year_from'] : null;
$yearTo = isset($_GET['year_to']) ? (int)$_GET['year_to'] : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;

// Initialize variables
$allCategories = [];
$movies = [];
$errorMessage = '';
$totalMovies = 0;
$totalPages = 1;
$userFavorites = [];
$userWatchLater = [];
$userRatings = [];

try {
    // Initialize database and repositories
    $database = new Database();
    $movieRepo = new MovieRepository($database);
    $favoritesRepo = new FavoritesRepository($database);
    $watchLaterRepo = new WatchLaterRepository($database->connect());
    $ratingRepo = new RatingRepository($database);

    // Fetch all categories
    $allCategories = $movieRepo->getAllCategories();
    
    // Use advanced filtering if Phase 2 parameters are present
    $useAdvancedFiltering = !empty($categories) || !empty($searchText) || $yearFrom || $yearTo;
    
    if ($useAdvancedFiltering) {
        // Phase 2: Advanced filtering with FilterBuilder
        require_once __DIR__ . '/src/FilterBuilder.php';
        
        $builder = new FilterBuilder();
        
        if (!empty($categories)) {
            $builder->withCategories($categories);
        }
        if ($minScore > 0) {
            $builder->withScoreRange($minScore, 10.0);
        }
        if ($yearFrom || $yearTo) {
            $builder->withYearRange($yearFrom, $yearTo);
        }
        if (!empty($searchText)) {
            $builder->withSearchText($searchText);
        }
        
        $builder->withSorting('score', 'DESC');
        $builder->withPagination($perPage, ($page - 1) * $perPage);
        
        $movies = $builder->execute($database->connect());
        
        // Get total count for pagination
        $countBuilder = new FilterBuilder();
        if (!empty($categories)) {
            $countBuilder->withCategories($categories);
        }
        if ($minScore > 0) {
            $countBuilder->withScoreRange($minScore, 10.0);
        }
        if ($yearFrom || $yearTo) {
            $countBuilder->withYearRange($yearFrom, $yearTo);
        }
        if (!empty($searchText)) {
            $countBuilder->withSearchText($searchText);
        }
        $totalMovies = count($countBuilder->execute($database->connect()));
        
    } else {
        // Phase 1: Basic filtering (backward compatible)
        $allMoviesResult = $movieRepo->findByFilters($selectedCategory, $minScore);
        $totalMovies = count($allMoviesResult);
        
        // Manual pagination for Phase 1
        $offset = ($page - 1) * $perPage;
        $movies = array_slice($allMoviesResult, $offset, $perPage);
    }
    
    $totalPages = max(1, ceil($totalMovies / $perPage));
    
    // Get user's favorites, watch later, and ratings
    $userId = $_SESSION['user_id'];
    $userFavoritesData = $favoritesRepo->getFavorites($userId);
    $userWatchLaterData = $watchLaterRepo->getWatchLater($userId, false); // false = unwatched only
    
    // Convert to lookup arrays for quick checks
    foreach ($userFavoritesData as $fav) {
        $userFavorites[$fav['id']] = true;
    }
    foreach ($userWatchLaterData as $wl) {
        $userWatchLater[$wl['id']] = true;
    }
    
    // Get user ratings for displayed movies
    foreach ($movies as $movie) {
        $rating = $ratingRepo->getUserRating($userId, $movie['id']);
        if ($rating) {
            $userRatings[$movie['id']] = $rating['rating'];
        }
    }
    
} catch (\Exception $e) {
    // Log error for debugging
    error_log("Application error: " . $e->getMessage());
    $errorMessage = "Unable to load movies at this time. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Suggestor - Phase 2</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #667eea; }
        h1 { color: #333; font-size: 2em; }
        .user-info { text-align: right; color: #666; font-size: 14px; }
        
        /* Filters - Enhanced */
        .filters { background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e0e0e0; }
        .filter-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-row:last-child { margin-bottom: 0; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group.full-width { flex: 100%; }
        .filter-group.multi-select { min-width: 300px; }
        
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        select, input[type="number"], input[type="text"] { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; transition: border-color 0.3s; }
        select:focus, input:focus { outline: none; border-color: #667eea; }
        
        /* Multi-select styling */
        select[multiple] { height: 120px; }
        
        .button-group { display: flex; gap: 10px; align-items: flex-end; }
        button { background: #667eea; color: white; padding: 11px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s; }
        button:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        button.secondary { background: #6c757d; }
        button.secondary:hover { background: #5a6268; }
        
        /* Movies Grid */
        .movies { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-bottom: 30px; }
        
        /* Movie Card - Enhanced */
        .movie-card { background: white; border: 2px solid #e0e0e0; border-radius: 10px; padding: 20px; transition: all 0.3s; position: relative; overflow: hidden; }
        .movie-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); border-color: #667eea; }
        .movie-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #667eea, #764ba2); }
        
        .movie-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 10px; line-height: 1.3; }
        
        .movie-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px; }
        .category { color: #667eea; font-weight: 600; font-size: 13px; background: #f0f2ff; padding: 4px 10px; border-radius: 12px; }
        .score { color: #28a745; font-weight: bold; font-size: 15px; }
        .year { color: #999; font-size: 13px; }
        
        .description { color: #666; font-size: 14px; line-height: 1.5; margin-bottom: 15px; max-height: 4.5em; overflow: hidden; }
        
        /* Action Buttons */
        .movie-actions { display: flex; gap: 8px; margin-top: 15px; flex-wrap: wrap; }
        .action-btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; flex: 1; min-width: 80px; }
        .action-btn:hover { transform: scale(1.05); }
        
        .favorite-btn { background: #fff; border: 2px solid #e74c3c; color: #e74c3c; }
        .favorite-btn.active { background: #e74c3c; color: white; }
        .favorite-btn:hover { background: #e74c3c; color: white; }
        
        .watchlater-btn { background: #fff; border: 2px solid #3498db; color: #3498db; }
        .watchlater-btn.active { background: #3498db; color: white; }
        .watchlater-btn:hover { background: #3498db; color: white; }
        
        .trailer-link { display: inline-flex; align-items: center; justify-content: center; background: #ff0000; color: white; padding: 8px 12px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600; flex: 1; min-width: 80px; transition: all 0.2s; }
        .trailer-link:hover { background: #cc0000; transform: scale(1.05); }
        
        /* Rating Stars */
        .rating-section { margin-top: 12px; padding-top: 12px; border-top: 1px solid #e0e0e0; }
        .rating-display { font-size: 14px; color: #666; margin-bottom: 5px; }
        .stars { display: flex; gap: 5px; align-items: center; }
        .star { font-size: 20px; cursor: pointer; color: #ddd; transition: all 0.2s; user-select: none; }
        .star:hover, .star.active { color: #ffc107; transform: scale(1.2); }
        .user-rating-text { font-size: 12px; color: #28a745; margin-left: 10px; font-weight: 600; }
        
        /* Pagination */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px; flex-wrap: wrap; }
        .pagination button { background: #fff; color: #667eea; border: 2px solid #667eea; padding: 8px 16px; min-width: 40px; }
        .pagination button:hover { background: #667eea; color: white; }
        .pagination button.active { background: #667eea; color: white; }
        .pagination button:disabled { background: #f0f0f0; color: #ccc; border-color: #ccc; cursor: not-allowed; }
        .pagination span { color: #666; font-size: 14px; }
        
        /* Messages */
        .error-message { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .success-message { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #c3e6cb; position: fixed; top: 20px; right: 20px; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideIn 0.3s; }
        
        @keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        .no-results { text-align: center; padding: 60px 20px; color: #999; font-size: 18px; }
        .no-results::before { content: '🎬'; display: block; font-size: 60px; margin-bottom: 20px; opacity: 0.3; }
        
        /* Loading State */
        .loading { text-align: center; padding: 40px; color: #667eea; font-size: 16px; }
        .loading::after { content: '...'; animation: dots 1.5s infinite; }
        @keyframes dots { 0%, 20% { content: '.'; } 40% { content: '..'; } 60%, 100% { content: '...'; } }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .filter-row { flex-direction: column; }
            .filter-group { min-width: 100%; }
            .movies { grid-template-columns: 1fr; }
            .button-group { width: 100%; }
            button { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 Movie Suggestor</h1>
            <div class="user-info">
                <strong><?= htmlspecialchars($_SESSION['username']) ?></strong><br>
                <small>User ID: <?= $_SESSION['user_id'] ?></small>
            </div>
        </div>
        
        <div id="success-message" style="display: none;" class="success-message"></div>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="error-message">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <form method="GET" class="filters" id="filterForm">
            <div class="filter-row">
                <!-- Phase 2: Multi-select categories -->
                <div class="filter-group multi-select">
                    <label for="categories">Categories (Multi-select):</label>
                    <select name="categories[]" id="categories" multiple>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                    <?= in_array($cat, $categories) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #999; font-size: 11px;">Hold Ctrl/Cmd to select multiple</small>
                </div>
                
                <!-- Phase 1: Single category (backward compatible) -->
                <div class="filter-group">
                    <label for="category">Or Single Category:</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                    <?= $cat === $selectedCategory ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="min_score">Minimum Score:</label>
                    <input type="number" 
                           name="min_score" 
                           id="min_score" 
                           step="0.1" 
                           min="0" 
                           max="10" 
                           value="<?= htmlspecialchars($minScore) ?>"
                           placeholder="0.0">
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="year_from">Year From:</label>
                    <input type="number" 
                           name="year_from" 
                           id="year_from" 
                           min="1900" 
                           max="2030" 
                           value="<?= htmlspecialchars($yearFrom ?? '') ?>"
                           placeholder="e.g., 2000">
                </div>
                
                <div class="filter-group">
                    <label for="year_to">Year To:</label>
                    <input type="number" 
                           name="year_to" 
                           id="year_to" 
                           min="1900" 
                           max="2030" 
                           value="<?= htmlspecialchars($yearTo ?? '') ?>"
                           placeholder="e.g., 2024">
                </div>
                
                <div class="filter-group" style="flex: 2;">
                    <label for="search">Search Text:</label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           value="<?= htmlspecialchars($searchText) ?>"
                           placeholder="Search in title or description...">
                </div>
            </div>
            
            <div class="filter-row">
                <div class="button-group" style="flex: 1; justify-content: flex-start;">
                    <button type="submit">🔍 Search Movies</button>
                    <button type="button" class="secondary" onclick="resetFilters()">↻ Reset Filters</button>
                </div>
            </div>
        </form>

        <div class="movies" id="moviesGrid">
            <?php if (empty($movies)): ?>
                <div class="no-results" style="grid-column: 1 / -1;">
                    No movies found matching your criteria. Try adjusting your filters!
                </div>
            <?php else: ?>
                <?php foreach ($movies as $movie): ?>
                    <div class="movie-card" data-movie-id="<?= $movie['id'] ?>">
                        <div class="movie-title"><?= htmlspecialchars($movie['title']) ?></div>
                        <div class="movie-meta">
                            <span class="category"><?= htmlspecialchars($movie['category']) ?></span>
                            <span class="score">⭐ <?= htmlspecialchars($movie['score']) ?></span>
                            <?php if (!empty($movie['year'])): ?>
                                <span class="year"><?= htmlspecialchars($movie['year']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="description"><?= htmlspecialchars($movie['description']) ?></div>
                        
                        <!-- Action Buttons -->
                        <div class="movie-actions">
                            <button class="action-btn favorite-btn <?= isset($userFavorites[$movie['id']]) ? 'active' : '' ?>" 
                                    onclick="toggleFavorite(<?= $movie['id'] ?>, this)">
                                ❤️ <?= isset($userFavorites[$movie['id']]) ? 'Favorited' : 'Favorite' ?>
                            </button>
                            
                            <button class="action-btn watchlater-btn <?= isset($userWatchLater[$movie['id']]) ? 'active' : '' ?>" 
                                    onclick="toggleWatchLater(<?= $movie['id'] ?>, this)">
                                📌 <?= isset($userWatchLater[$movie['id']]) ? 'In List' : 'Watch Later' ?>
                            </button>
                            
                            <?php if (!empty($movie['trailer_url'])): ?>
                                <a href="<?= htmlspecialchars($movie['trailer_url']) ?>" 
                                   target="_blank" 
                                   class="trailer-link">
                                    ▶️ Trailer
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Rating Section -->
                        <div class="rating-section">
                            <div class="rating-display">
                                <small>Rate this movie:</small>
                            </div>
                            <div class="stars" data-movie-id="<?= $movie['id'] ?>">
                                <?php 
                                $userRating = $userRatings[$movie['id']] ?? 0;
                                for ($i = 1; $i <= 5; $i++): 
                                    $isActive = ($i <= ceil($userRating / 2)) ? 'active' : '';
                                ?>
                                    <span class="star <?= $isActive ?>" data-rating="<?= $i * 2 ?>" onclick="rateMovie(<?= $movie['id'] ?>, <?= $i * 2 ?>)">★</span>
                                <?php endfor; ?>
                                <?php if ($userRating > 0): ?>
                                    <span class="user-rating-text">Your rating: <?= number_format($userRating, 1) ?>/10</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <button onclick="goToPage(1)" <?= $page == 1 ? 'disabled' : '' ?>>«</button>
                <button onclick="goToPage(<?= max(1, $page - 1) ?>)" <?= $page == 1 ? 'disabled' : '' ?>>‹ Prev</button>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <button onclick="goToPage(<?= $i ?>)" class="<?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </button>
                <?php endfor; ?>
                
                <button onclick="goToPage(<?= min($totalPages, $page + 1) ?>)" <?= $page == $totalPages ? 'disabled' : '' ?>>Next ›</button>
                <button onclick="goToPage(<?= $totalPages ?>)" <?= $page == $totalPages ? 'disabled' : '' ?>>»</button>
                
                <span>Page <?= $page ?> of <?= $totalPages ?> (<?= $totalMovies ?> movies)</span>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // API endpoint for AJAX calls
        const API_URL = 'api.php';
        const USER_ID = <?= $_SESSION['user_id'] ?>;
        
        // Show success message
        function showMessage(message, duration = 3000) {
            const msgEl = document.getElementById('success-message');
            msgEl.textContent = message;
            msgEl.style.display = 'block';
            
            setTimeout(() => {
                msgEl.style.display = 'none';
            }, duration);
        }
        
        // Toggle Favorite
        async function toggleFavorite(movieId, button) {
            const isActive = button.classList.contains('active');
            const action = isActive ? 'remove' : 'add';
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'favorite',
                        operation: action,
                        user_id: USER_ID,
                        movie_id: movieId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    button.classList.toggle('active');
                    button.textContent = isActive ? '❤️ Favorite' : '❤️ Favorited';
                    showMessage(isActive ? 'Removed from favorites' : 'Added to favorites!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to update favorite'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update favorite. Please try again.');
            }
        }
        
        // Toggle Watch Later
        async function toggleWatchLater(movieId, button) {
            const isActive = button.classList.contains('active');
            const action = isActive ? 'remove' : 'add';
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'watchlater',
                        operation: action,
                        user_id: USER_ID,
                        movie_id: movieId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    button.classList.toggle('active');
                    button.textContent = isActive ? '📌 Watch Later' : '📌 In List';
                    showMessage(isActive ? 'Removed from watch later' : 'Added to watch later!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to update watch later'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update watch later. Please try again.');
            }
        }
        
        // Rate Movie
        async function rateMovie(movieId, rating) {
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'rate',
                        user_id: USER_ID,
                        movie_id: movieId,
                        rating: rating
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update stars display
                    const starsContainer = document.querySelector(`.stars[data-movie-id="${movieId}"]`);
                    const stars = starsContainer.querySelectorAll('.star');
                    const maxActive = Math.ceil(rating / 2);
                    
                    stars.forEach((star, index) => {
                        if (index < maxActive) {
                            star.classList.add('active');
                        } else {
                            star.classList.remove('active');
                        }
                    });
                    
                    // Update or add rating text
                    let ratingText = starsContainer.querySelector('.user-rating-text');
                    if (!ratingText) {
                        ratingText = document.createElement('span');
                        ratingText.className = 'user-rating-text';
                        starsContainer.appendChild(ratingText);
                    }
                    ratingText.textContent = `Your rating: ${rating.toFixed(1)}/10`;
                    
                    showMessage(`Rated ${rating}/10!`);
                } else {
                    alert('Error: ' + (data.message || 'Failed to submit rating'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to submit rating. Please try again.');
            }
        }
        
        // Pagination
        function goToPage(page) {
            const url = new URL(window.location);
            url.searchParams.set('page', page);
            window.location = url.toString();
        }
        
        // Reset Filters
        function resetFilters() {
            document.getElementById('filterForm').reset();
            window.location.href = window.location.pathname;
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('search').focus();
            }
        });
    </script>
</body>
</html>
