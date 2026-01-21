<?php
/**
 * My Favorites Page
 * Display all movies the user has favorited
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Security.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/FavoritesRepository.php';

use MovieSuggestor\Security;
use MovieSuggestor\Database;
use MovieSuggestor\FavoritesRepository;

// Initialize secure session and require authentication
Security::initSession();
Security::requireAuth();

$userId = Security::getUserId();
$username = $_SESSION['username'] ?? 'User';

// Get user's favorites
$database = new Database();
$favoritesRepo = new FavoritesRepository($database);
$favorites = $favoritesRepo->getFavorites($userId);
$count = $favoritesRepo->getFavoritesCount($userId);

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Movie Suggestor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 2em;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        
        .content {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .stats {
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            color: white;
        }
        
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .movie-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .movie-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .movie-info {
            padding: 15px;
        }
        
        .movie-title {
            font-weight: 600;
            font-size: 1.1em;
            margin-bottom: 8px;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .movie-meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .movie-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
        }
        
        .btn-remove:hover {
            background: #c82333;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state h2 {
            font-size: 2em;
            margin-bottom: 15px;
            color: #999;
        }
        
        .empty-state p {
            font-size: 1.1em;
            margin-bottom: 25px;
        }
        
        .empty-state a {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .empty-state a:hover {
            background: #764ba2;
        }
        
        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: #28a745;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>❤️ My Favorites</h1>
                <p>Welcome, <strong><?= htmlspecialchars($username) ?></strong></p>
            </div>
            <div class="nav-links">
                <a href="index.php">🏠 Home</a>
                <a href="auth/profile.php">👤 Profile</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
        
        <div class="content">
            <?php if ($count > 0): ?>
                <div class="stats">
                    <h2>📊 Your Collection</h2>
                    <p style="font-size: 1.2em; margin-top: 10px;">You have <strong><?= $count ?></strong> favorite movies</p>
                </div>
                
                <div class="movies-grid">
                    <?php foreach ($favorites as $movie): ?>
                        <div class="movie-card" data-tmdb-id="<?= htmlspecialchars($movie['tmdb_id']) ?>">
                            <a href="movie-details.php?id=<?= htmlspecialchars($movie['tmdb_id']) ?>" style="text-decoration: none; color: inherit;">
                            <img src="<?= htmlspecialchars($movie['poster_url'] ?? 'https://via.placeholder.com/200x300?text=No+Poster') ?>" 
                                 alt="<?= htmlspecialchars($movie['movie_title']) ?>"
                                 onerror="this.src='https://via.placeholder.com/200x300?text=No+Poster'">
                            <div class="movie-info">
                                <div class="movie-title"><?= htmlspecialchars($movie['movie_title']) ?></div>
                                <div class="movie-meta">
                                    <?= htmlspecialchars($movie['release_year'] ?? 'N/A') ?>
                                    <?php if (!empty($movie['category'])): ?>
                                        • <?= htmlspecialchars($movie['category']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            </a>
                                <div class="movie-actions">
                                    <button class="btn btn-remove" onclick="removeFavorite(<?= $movie['tmdb_id'] ?>, this)">
                                        🗑️ Remove
                                    </button>
                                </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h2>💔 No Favorites Yet</h2>
                    <p>Start adding movies to your favorites collection!</p>
                    <a href="index.php">Browse Movies</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="message" id="message"></div>
    
    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';
        
        function showMessage(text, isError = false) {
            const msg = document.getElementById('message');
            msg.textContent = text;
            msg.style.background = isError ? '#dc3545' : '#28a745';
            msg.style.display = 'block';
            setTimeout(() => msg.style.display = 'none', 3000);
        }
        
        async function removeFavorite(tmdbId, button) {
            if (!confirm('Remove this movie from your favorites?')) {
                return;
            }
            
            const card = button.closest('.movie-card');
            
            try {
                const response = await fetch('api/favorites.php', {
                    method: 'DELETE',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ tmdb_id: tmdbId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        card.remove();
                        const remaining = document.querySelectorAll('.movie-card').length;
                        if (remaining === 0) {
                            location.reload();
                        }
                    }, 300);
                    showMessage('Removed from favorites!');
                } else {
                    showMessage('Error: ' + (data.error || 'Failed to remove'), true);
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Failed to remove. Please try again.', true);
            }
        }
    </script>
</body>
</html>
