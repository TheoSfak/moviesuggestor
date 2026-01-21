<?php

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $envFile = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envFile as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Security.php';

use MovieSuggestor\Security;

// Initialize secure session
Security::initSession();

// Check if user wants to continue as guest
$guestMode = isset($_GET['guest']) && $_GET['guest'] === '1';

if ($guestMode) {
    // Guest mode: Create temporary session for demo
    if (!isset($_SESSION['guest_id'])) {
        $_SESSION['guest_id'] = 'guest_' . uniqid();
        $_SESSION['user_id'] = 999999; // Special guest user ID
        $_SESSION['username'] = 'Guest User';
        $_SESSION['is_guest'] = true;
    }
} else {
    // Require authentication for full features
    Security::requireAuth();
}

// Get authenticated user ID (never trust client input)
$userId = Security::getUserId();

use MovieSuggestor\Database;
use MovieSuggestor\TMDBService;
use MovieSuggestor\FavoritesRepository;
use MovieSuggestor\RatingRepository;

// Get filter parameters
$selectedCategory = $_GET['category'] ?? '';
$minScore = isset($_GET['min_score']) ? (float)$_GET['min_score'] : 0.0;
$categories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
$searchText = $_GET['search'] ?? '';
$yearFrom = isset($_GET['year_from']) ? (int)$_GET['year_from'] : null;
$yearTo = isset($_GET['year_to']) ? (int)$_GET['year_to'] : null;
$language = $_GET['language'] ?? '';
$popular = !empty($_GET['popular']);
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20; // TMDB default per page

// Initialize variables
$allCategories = ['Δράμα', 'Κωμωδία', 'Δράση', 'Περιπέτεια', 'Ρομαντική', 'Θρίλερ', 'Τρόμου', 'Αστυνομική'];
$moviesList = [];
$errorMessage = '';
$totalMovies = 0;
$totalPages = 1;
$userFavorites = [];
$userRatings = [];

try {
    // Initialize TMDB Service and database repositories
    $tmdbService = new TMDBService();
    $database = new Database();
    $favoritesRepo = new FavoritesRepository($database);
    $ratingRepo = new RatingRepository($database);

    // Map Greek categories to TMDB genre IDs
    $genreMap = [
        'Δράμα' => 18,
        'Κωμωδία' => 35,
        'Δράση' => 28,
        'Περιπέτεια' => 12,
        'Ρομαντική' => 10749,
        'Θρίλερ' => 53,
        'Τρόμου' => 27,
        'Αστυνομική' => 80
    ];

    // Build TMDB filters
    $filters = ['page' => $page];
    
    // Handle categories
    $activeCategories = !empty($categories) ? $categories : (!empty($selectedCategory) ? [$selectedCategory] : []);
    if (!empty($activeCategories)) {
        // Pass Greek category names - TMDBService will convert them to genre IDs
        $filters['categories'] = $activeCategories;
    }
    
    // Minimum score filter
    if ($minScore > 0) {
        $filters['vote_average_gte'] = $minScore;
    }
    
    // Year range filters
    if ($yearFrom) {
        $filters['year_from'] = $yearFrom;
    }
    if ($yearTo) {
        $filters['year_to'] = $yearTo;
    }
    
    // Language filter
    if (!empty($language)) {
        $filters['with_original_language'] = $language;
    }
    
    // Popularity sort for popular button
    if ($popular) {
        $filters['sort_by'] = 'popularity.desc';
    }
    
    // Fetch movies from TMDB
    if (!empty($searchText)) {
        $tmdbResponse = $tmdbService->searchMovies($searchText, $page);
    } else {
        $tmdbResponse = $tmdbService->discoverMovies($filters);
    }
    
    if (isset($tmdbResponse['success']) && $tmdbResponse['success']) {
        $totalMovies = $tmdbResponse['total_results'] ?? 0;
        $totalPages = $tmdbResponse['total_pages'] ?? 1;
        $moviesList = $tmdbResponse['results'] ?? [];
        
        // Filter results client-side if single category selected
        // TMDB returns movies with multiple genres, we want to show only the primary category
        if (!empty($selectedCategory) && !empty($moviesList)) {
            $moviesList = array_filter($moviesList, function($movie) use ($selectedCategory) {
                return isset($movie['category']) && $movie['category'] === $selectedCategory;
            });
            $moviesList = array_values($moviesList); // Re-index array
        }
    } else {
        $errorMessage = $tmdbResponse['error'] ?? 'Failed to load movies from TMDB';
    }
    
    // Get user's favorites and ratings (by tmdb_id)
    $userFavoritesData = $favoritesRepo->getFavorites($userId);
    
    // Convert to lookup arrays by tmdb_id
    foreach ($userFavoritesData as $fav) {
        if (!empty($fav['tmdb_id'])) {
            $userFavorites[$fav['tmdb_id']] = true;
        }
    }
    
    // Note: Ratings will be loaded dynamically as needed
    
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
        
        .trailer-btn { background: #E50914; color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; flex: 1; min-width: 100px; }
        .trailer-btn:hover { background: #b20710; transform: scale(1.05); }
        .trailer-btn:disabled { background: #ccc; cursor: not-allowed; opacity: 0.6; }
        
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
                <small>
                    <?php if (isset($_SESSION['is_guest']) && $_SESSION['is_guest']): ?>
                        Guest Mode | <a href="login.php" style="color: #667eea;">Sign In</a>
                    <?php else: ?>
                        <a href="my-favorites.php" style="color: #e74c3c; font-weight: 600;">❤️ My Favorites</a> | 
                        <a href="auth/profile.php" style="color: #667eea;">Profile</a> | 
                        <a href="logout.php" style="color: #667eea;">Logout</a>
                    <?php endif; ?>
                </small>
            </div>
        </div>
        
        <?php if (isset($_SESSION['is_guest']) && $_SESSION['is_guest']): ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin-bottom: 20px; color: #856404;">
                <strong>⚠️ Guest Mode:</strong> You're viewing in limited mode. 
                <a href="register.php" style="color: #667eea; font-weight: 600;">Create an account</a> to save favorites, ratings, and watch later lists.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['registered']) && $_GET['registered'] === '1'): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 6px; margin-bottom: 20px; color: #155724;">
                <strong>✅ Welcome!</strong> Your account has been created successfully. Start exploring movies!
            </div>
        <?php endif; ?>
        
        <div id="success-message" style="display: none;" class="success-message"></div>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="error-message">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <!-- Main Filters -->
        <h2 style="margin-bottom: 15px; color: #333;">🎬 Online Movie Search (TMDB)</h2>
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
                
                <div class="filter-group">
                    <label for="language">Language:</label>
                    <select name="language" id="language">
                        <option value="">All Languages</option>
                        <option value="en">English</option>
                        <option value="el">Greek (Ελληνικά)</option>
                        <option value="es">Spanish</option>
                        <option value="fr">French</option>
                        <option value="de">German</option>
                        <option value="it">Italian</option>
                        <option value="ja">Japanese</option>
                        <option value="ko">Korean</option>
                        <option value="zh">Chinese</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="button-group" style="flex: 1; justify-content: flex-start;">
                    <button type="submit">🔍 Search Movies</button>
                    <button type="button" onclick="loadPopularWithFilters()" style="background: #E50914;">🔥 Popular</button>
                    <button type="button" class="secondary" onclick="resetFilters()">↻ Reset Filters</button>
                </div>
            </div>
        </form>

        <div class="movies" id="moviesGrid">
            <?php if (empty($moviesList)): ?>
                <div class="no-results" style="grid-column: 1 / -1;">
                    No movies found matching your criteria. Try adjusting your filters!
                </div>
            <?php else: ?>
                <?php foreach ($moviesList as $movie): 
                    $tmdbId = $movie['tmdb_id'] ?? 0;
                    $title = $movie['title'] ?? 'Unknown';
                    $posterUrl = $movie['poster_url'] ?? 'https://via.placeholder.com/500x750?text=No+Poster';
                    $category = $movie['category'] ?? 'Unknown';
                    $score = isset($movie['vote_average']) ? number_format($movie['vote_average'], 1) : 'N/A';
                    $year = $movie['release_year'] ?? '';
                    $description = $movie['description'] ?? 'No description available';
                    $imdbRating = $movie['imdb_rating'] ?? null;
                ?>
                    <div class="movie-card" data-tmdb-id="<?= $tmdbId ?>" data-title="<?= htmlspecialchars($title) ?>" data-poster="<?= htmlspecialchars($posterUrl) ?>" data-year="<?= $year ?>" data-category="<?= htmlspecialchars($category) ?>">
                        <a href="movie-details.php?id=<?= $tmdbId ?>" style="text-decoration: none; color: inherit;">
                        <?php if (!empty($posterUrl)): ?>
                        <img src="<?= htmlspecialchars($posterUrl) ?>" 
                             alt="<?= htmlspecialchars($title) ?>" 
                             style="width: 100%; height: 200px; object-fit: cover; border-radius: 6px; margin-bottom: 10px;"
                             onerror="this.src='https://via.placeholder.com/500x750?text=No+Poster'">
                        <?php endif; ?>
                        <div class="movie-title"><?= htmlspecialchars($title) ?></div>
                        </a>
                        <?php if (!empty($movie['original_title']) && $movie['original_title'] !== $title): ?>
                            <div style="font-size: 13px; color: #999; font-style: italic; margin-bottom: 8px;"><?= htmlspecialchars($movie['original_title']) ?></div>
                        <?php endif; ?>
                        <div class="movie-meta">
                            <span class="category"><?= htmlspecialchars($category) ?></span>
                            <span class="score">⭐ <?= htmlspecialchars($score) ?></span>
                            <?php if (!empty($imdbRating)): ?>
                                <span class="score" style="background: #f5c518; color: #000; padding: 4px 8px; border-radius: 4px; font-weight: bold;">IMDb <?= htmlspecialchars($imdbRating) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($year)): ?>
                                <span class="year"><?= htmlspecialchars($year) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="description"><?= htmlspecialchars($description) ?></div>
                        
                        <!-- Action Buttons -->
                        <div class="movie-actions">
                            <button class="action-btn favorite-btn <?= isset($userFavorites[$tmdbId]) ? 'active' : '' ?>" 
                                    onclick="toggleFavorite(<?= $tmdbId ?>, this)">
                                ❤️ <?= isset($userFavorites[$tmdbId]) ? 'Favorited' : 'Favorite' ?>
                            </button>
                            
                            <button class="action-btn trailer-btn" 
                                    onclick="watchTrailer(<?= $tmdbId ?>, this)">
                                🎬 Trailer
                            </button>
                        </div>
                        
                        <!-- Rating Section -->
                        <div class="rating-section">
                            <div class="rating-display">
                                <small>Rate this movie:</small>
                            </div>
                            <div class="stars" data-tmdb-id="<?= $tmdbId ?>">
                                <?php 
                                // Note: User ratings loaded dynamically
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <span class="star" data-rating="<?= $i * 2 ?>" onclick="rateMovie(<?= $tmdbId ?>, <?= $i * 2 ?>)">★</span>
                                <?php endfor; ?>
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
        const USER_ID = <?= $userId ?>;
        const IS_GUEST = <?= isset($_SESSION['is_guest']) && $_SESSION['is_guest'] ? 'true' : 'false' ?>;
        const CSRF_TOKEN = '<?= Security::generateCSRFToken() ?>';
        
        // Check if guest mode - warn about limited features
        function checkGuestMode() {
            if (IS_GUEST) {
                alert('⚠️ Guest Mode: This feature requires an account. Please sign in or register.');
                return false;
            }
            return true;
        }
        
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
        async function toggleFavorite(tmdbId, button) {
            if (!checkGuestMode()) return;
            
            const isActive = button.classList.contains('active');
            const action = isActive ? 'remove' : 'add';
            
            // Get movie data from card
            const card = button.closest('.movie-card');
            const movieData = {
                tmdb_id: tmdbId,
                title: card.dataset.title,
                poster_url: card.dataset.poster,
                release_year: card.dataset.year,
                category: card.dataset.category
            };
            
            try {
                const response = await fetch('api/favorites.php', {
                    method: isActive ? 'DELETE' : 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(movieData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    button.classList.toggle('active');
                    button.textContent = isActive ? '❤️ Favorite' : '❤️ Favorited';
                    showMessage(isActive ? 'Removed from favorites' : 'Added to favorites!');
                } else {
                    alert('Error: ' + (data.error || 'Failed to update favorite'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update favorite. Please try again.');
            }
        }
        
        // Toggle Watch Later
        
        // Rate Movie
        async function rateMovie(tmdbId, rating) {
            if (!checkGuestMode()) return;
            
            // Get movie data from card
            const card = document.querySelector(`.movie-card[data-tmdb-id="${tmdbId}"]`);
            const movieData = {
                tmdb_id: tmdbId,
                title: card.dataset.title,
                poster_url: card.dataset.poster,
                release_year: card.dataset.year,
                category: card.dataset.category
            };
            
            try {
                const response = await fetch('api/ratings.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        rating: rating,
                        ...movieData
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update stars display
                    const starsContainer = document.querySelector(`.stars[data-tmdb-id="${tmdbId}"]`);
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
                    alert('Error: ' + (data.error || 'Failed to submit rating'));
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
        
        // Load popular movies with current filters
        function loadPopularWithFilters() {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            
            // Build URL with current filters
            const params = new URLSearchParams();
            for (let [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }
            
            // Add popular flag
            params.append('popular', '1');
            
            // Navigate to filtered popular movies
            window.location.href = '?' + params.toString();
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('search').focus();
            }
        });

        // =====================================================
        // TMDB SEARCH FUNCTIONALITY
        // =====================================================
        
        let currentTMDBPage = 1;
        let currentTMDBQuery = '';
        let currentTMDBMode = 'search'; // 'search' or 'popular'

        // Search TMDB
        async function searchTMDB(page = 1) {
            const query = document.getElementById('tmdb-query').value.trim();
            
            if (!query && page === 1) {
                alert('Please enter a movie title to search');
                return;
            }
            
            currentTMDBPage = page;
            currentTMDBQuery = query;
            currentTMDBMode = 'search';
            
            showTMDBLoading();
            
            try {
                const response = await fetch(`api/tmdb-search.php?query=${encodeURIComponent(query)}&page=${page}`);
                const data = await response.json();
                
                if (data.success) {
                    displayTMDBResults(data);
                } else {
                    showTMDBError(data.error || 'Search failed');
                }
            } catch (error) {
                console.error('TMDB Search Error:', error);
                showTMDBError('Network error. Please check your connection.');
            }
        }

        // Get Popular Movies
        async function getPopularMovies(page = 1) {
            currentTMDBPage = page;
            currentTMDBMode = 'popular';
            
            showTMDBLoading();
            
            try {
                const response = await fetch(`api/tmdb-search.php?popular=1&page=${page}`);
                const data = await response.json();
                
                if (data.success) {
                    displayTMDBResults(data);
                } else {
                    showTMDBError(data.error || 'Failed to load popular movies');
                }
            } catch (error) {
                console.error('TMDB Popular Error:', error);
                showTMDBError('Network error. Please check your connection.');
            }
        }

        // Display TMDB Results
        function displayTMDBResults(data) {
            const resultsDiv = document.getElementById('tmdb-results');
            const gridDiv = document.getElementById('tmdb-movies-grid');
            const paginationDiv = document.getElementById('tmdb-pagination');
            
            hideTMDBLoading();
            hideTMDBError();
            
            if (!data.results || data.results.length === 0) {
                gridDiv.innerHTML = '<div class="no-results" style="grid-column: 1 / -1; color: white;">No movies found</div>';
                resultsDiv.style.display = 'block';
                return;
            }
            
            // Build movie cards
            let html = '';
            data.results.forEach(movie => {
                const posterUrl = movie.poster_url || 'https://via.placeholder.com/500x750?text=No+Poster';
                const year = movie.release_year || 'N/A';
                const rating = movie.vote_average || 'N/A';
                const description = movie.description ? (movie.description.substring(0, 150) + '...') : 'No description available';
                const imdbRating = movie.imdb_rating ? `<span class="score" style="background: #f5c518; color: #000; padding: 4px 8px; border-radius: 4px; font-weight: bold; margin-left: 5px;">IMDb ${movie.imdb_rating}</span>` : '';
                
                html += `
                    <div class="movie-card" style="background: white;">
                        <img src="${posterUrl}" 
                             alt="${escapeHtml(movie.title)}" 
                             style="width: 100%; height: 200px; object-fit: cover; border-radius: 6px; margin-bottom: 10px;"
                             onerror="this.src='https://via.placeholder.com/500x750?text=No+Poster'">
                        <div class="movie-title">${escapeHtml(movie.title)}</div>
                        <div class="movie-meta">
                            <span class="category">${escapeHtml(movie.category)}</span>
                            <span class="score">⭐ ${rating}</span>
                            ${imdbRating}
                            <span class="year">${year}</span>
                        </div>
                        <div class="description">${escapeHtml(description)}</div>
                        <div class="movie-actions">
                            <button class="action-btn" 
                                    style="background: #28a745; color: white; flex: 1;"
                                    onclick="importMovie(${movie.tmdb_id}, this)">
                                💾 Import to Database
                            </button>
                        </div>
                    </div>
                `;
            });
            
            gridDiv.innerHTML = html;
            
            // Build pagination
            let paginationHtml = '';
            if (data.total_pages > 1) {
                paginationHtml += `<button onclick="goToTMDBPage(${data.page - 1})" ${data.page === 1 ? 'disabled' : ''}>◀ Previous</button>`;
                paginationHtml += `<span>Page ${data.page} of ${Math.min(data.total_pages, 500)}</span>`;
                paginationHtml += `<button onclick="goToTMDBPage(${data.page + 1})" ${data.page >= data.total_pages ? 'disabled' : ''}>Next ▶</button>`;
            }
            paginationDiv.innerHTML = paginationHtml;
            
            resultsDiv.style.display = 'block';
        }

        // Import Movie to Database
        async function importMovie(tmdbId, button) {
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Importing...';
            button.disabled = true;
            
            try {
                const response = await fetch('api/import-movie.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tmdb_id: tmdbId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    button.innerHTML = '✓ Imported!';
                    button.style.background = '#666';
                    showMessage(`"${data.movie.title}" imported successfully!`);
                    
                    // Optionally reload local movies
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    if (data.error.includes('already exists')) {
                        showMessage('Movie already in database', 'warning');
                    } else {
                        alert('Import failed: ' + data.error);
                    }
                }
            } catch (error) {
                console.error('Import Error:', error);
                button.innerHTML = originalText;
                button.disabled = false;
                alert('Network error. Please try again.');
            }
        }

        // Go to TMDB Page
        function goToTMDBPage(page) {
            if (currentTMDBMode === 'popular') {
                getPopularMovies(page);
            } else {
                searchTMDB(page);
            }
        }

        // Show TMDB Loading
        function showTMDBLoading() {
            document.getElementById('tmdb-loading').style.display = 'block';
            document.getElementById('tmdb-results').style.display = 'none';
            document.getElementById('tmdb-error').style.display = 'none';
        }

        // Hide TMDB Loading
        function hideTMDBLoading() {
            document.getElementById('tmdb-loading').style.display = 'none';
        }

        // Show TMDB Error
        function showTMDBError(message) {
            const errorDiv = document.getElementById('tmdb-error');
            const errorMsg = document.getElementById('tmdb-error-message');
            errorMsg.textContent = message;
            errorDiv.style.display = 'block';
            hideTMDBLoading();
        }

        // Hide TMDB Error
        function hideTMDBError() {
            document.getElementById('tmdb-error').style.display = 'none';
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Allow Enter key to search TMDB
        document.getElementById('tmdb-query').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchTMDB();
            }
        });
        
        // Watch Trailer Function
        async function watchTrailer(tmdbId, button) {
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Loading...';
            button.disabled = true;
            
            try {
                const response = await fetch(`api/tmdb-search.php?trailer=${tmdbId}`);
                const data = await response.json();
                
                if (data.success && data.trailer_url) {
                    window.open(data.trailer_url, '_blank');
                    button.innerHTML = originalText;
                    button.disabled = false;
                } else {
                    alert('Trailer not available for this movie');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Trailer Error:', error);
                alert('Failed to load trailer');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
    </script>
</body>
</html>
