<?php

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

require_once __DIR__ . '/vendor/autoload.php';

use MovieSuggestor\Database;
use MovieSuggestor\MovieRepository;

// Get filter parameters
$selectedCategory = $_GET['category'] ?? '';
$minScore = isset($_GET['min_score']) ? (float)$_GET['min_score'] : 0.0;

// Initialize variables
$categories = [];
$movies = [];
$errorMessage = '';

try {
    // Initialize database and repository
    $database = new Database();
    $repository = new MovieRepository($database);

    // Fetch data
    $categories = $repository->getAllCategories();
    $movies = $repository->findByFilters($selectedCategory, $minScore);
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
    <title>Movie Suggestor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; }
        .filters { display: flex; gap: 20px; margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 4px; }
        .filter-group { flex: 1; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        select, input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        button { background: #007bff; color: white; padding: 10px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .movies { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .movie-card { background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; transition: transform 0.2s; }
        .movie-card:hover { transform: translateY(-5px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .movie-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .movie-meta { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .category { color: #007bff; font-weight: bold; }
        .score { color: #28a745; font-weight: bold; }
        .description { color: #666; font-size: 14px; line-height: 1.4; margin-bottom: 15px; }
        .trailer-link { display: inline-block; background: #ff0000; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .trailer-link:hover { background: #cc0000; }
        .no-results { text-align: center; padding: 40px; color: #999; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Movie Suggestor</h1>
        
        <?php if (!empty($errorMessage)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <form method="GET" class="filters">
            <div class="filter-group">
                <label for="category">Category:</label>
                <select name="category" id="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
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
            
            <div class="filter-group" style="display: flex; align-items: flex-end;">
                <button type="submit">Search Movies</button>
            </div>
        </form>

        <div class="movies">
            <?php if (empty($movies)): ?>
                <div class="no-results">
                    No movies found matching your criteria. Try adjusting your filters!
                </div>
            <?php else: ?>
                <?php foreach ($movies as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-title"><?= htmlspecialchars($movie['title']) ?></div>
                        <div class="movie-meta">
                            <span class="category"><?= htmlspecialchars($movie['category']) ?></span>
                            <span class="score">⭐ <?= htmlspecialchars($movie['score']) ?></span>
                        </div>
                        <div class="description"><?= htmlspecialchars($movie['description']) ?></div>
                        <?php if (!empty($movie['trailer_url'])): ?>
                            <a href="<?= htmlspecialchars($movie['trailer_url']) ?>" 
                               target="_blank" 
                               class="trailer-link">
                                Watch Trailer
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
