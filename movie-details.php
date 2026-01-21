<?php
// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $envFile = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envFile as $line) {
        if (strpos(trim($line), '#') === 0) continue;
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
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/TMDBService.php';

use MovieSuggestor\Security;
use MovieSuggestor\Database;
use MovieSuggestor\TMDBService;

// Initialize secure session
Security::initSession();

// Check if guest mode
$isGuest = isset($_SESSION['is_guest']) && $_SESSION['is_guest'] === true;
if (!$isGuest) {
    Security::requireAuth();
}

$userId = Security::getUserId();
$tmdbId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tmdbId) {
    header('Location: index.php');
    exit;
}

// Initialize services
$tmdb = new TMDBService();
$db = new Database();
$pdo = $db->connect();

// Fetch movie details from TMDB
$movie = $tmdb->getMovieDetails($tmdbId);

if (!$movie) {
    header('Location: index.php?error=movie_not_found');
    exit;
}

// Get videos (trailers, teasers, etc.)
$videos = $tmdb->getMovieVideos($tmdbId);
$trailer = null;
foreach ($videos as $video) {
    if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
        $trailer = 'https://www.youtube.com/embed/' . $video['key'];
        break;
    }
}

// Get movie images
$images = $tmdb->getMovieImages($tmdbId);

// Get similar movies
$similarMovies = $tmdb->getSimilarMovies($tmdbId, 1, 6);

// Check user's status with this movie
$userStatus = [
    'isFavorite' => false,
    'inWatchLater' => false,
    'userRating' => null,
    'userReview' => null
];

if (!$isGuest) {
    // Check if favorited
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND tmdb_id = ?");
    $stmt->execute([$userId, $tmdbId]);
    $userStatus['isFavorite'] = (bool)$stmt->fetch();
    
    // Check if in watch later
    $stmt = $pdo->prepare("SELECT id FROM watch_later WHERE user_id = ? AND tmdb_id = ?");
    $stmt->execute([$userId, $tmdbId]);
    $userStatus['inWatchLater'] = (bool)$stmt->fetch();
    
    // Get user's rating
    $stmt = $pdo->prepare("SELECT rating, review FROM ratings WHERE user_id = ? AND tmdb_id = ?");
    $stmt->execute([$userId, $tmdbId]);
    $userRating = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userRating) {
        $userStatus['userRating'] = $userRating['rating'];
        $userStatus['userReview'] = $userRating['review'];
    }
}

// Get all user reviews for this movie
$stmt = $pdo->prepare("
    SELECT r.rating, r.review, r.created_at, u.username 
    FROM ratings r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.tmdb_id = ? AND r.review IS NOT NULL AND r.review != ''
    ORDER BY r.created_at DESC 
    LIMIT 20
");
$stmt->execute([$tmdbId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get average user rating
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM ratings WHERE tmdb_id = ?");
$stmt->execute([$tmdbId]);
$ratingStats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> - Movie Suggestor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 50px;
        }
        .movie-header {
            position: relative;
            color: white;
            padding: 60px 0 40px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.7), rgba(0,0,0,0.9));
        }
        .backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.3;
            z-index: -1;
        }
        .poster-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .poster-container img {
            width: 100%;
            height: auto;
        }
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: -50px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .action-btn {
            margin: 5px;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 500;
        }
        .rating-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 1.2em;
            font-weight: bold;
            display: inline-block;
        }
        .genre-badge {
            background: #f0f0f0;
            padding: 5px 15px;
            border-radius: 20px;
            margin: 3px;
            display: inline-block;
            font-size: 0.9em;
        }
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 15px;
            margin: 20px 0;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .image-gallery img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .image-gallery img:hover {
            transform: scale(1.05);
        }
        .review-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .similar-movie-card {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }
        .similar-movie-card:hover {
            transform: translateY(-5px);
        }
        .similar-movie-card .card {
            height: 100%;
            border: 1px solid #ddd;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .similar-movie-card:hover .card {
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .similar-movie-card .card-img-top {
            width: 100%;
            height: 280px;
            object-fit: cover;
            border-radius: 0;
        }
        .similar-movie-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 80px;
        }
        .similar-movie-card .card-text {
            font-size: 0.875rem;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin-bottom: 8px;
            height: 36px;
        }
        .movie-info-item {
            margin: 15px 0;
        }
        .movie-info-item strong {
            color: #667eea;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-film"></i> Movie Suggestor
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <?php if (!$isGuest): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my-favorites.php"><i class="fas fa-heart"></i> Favorites</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-watch-later.php"><i class="fas fa-clock"></i> Watch Later</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/profile.php"><i class="fas fa-user"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Movie Header with Backdrop -->
    <div class="movie-header">
        <?php if (!empty($movie['backdrop_path'])): ?>
            <img src="<?= $movie['backdrop_path'] ?>" alt="Backdrop" class="backdrop">
        <?php endif; ?>
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="poster-container">
                        <img src="<?= $movie['poster_path'] ?? 'https://via.placeholder.com/300x450?text=No+Image' ?>" 
                             alt="<?= htmlspecialchars($movie['title']) ?>">
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="display-4 mb-3"><?= htmlspecialchars($movie['title']) ?></h1>
                    <?php if (!empty($movie['tagline'])): ?>
                        <p class="lead fst-italic mb-3">"<?= htmlspecialchars($movie['tagline']) ?>"</p>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <span class="rating-badge">
                            <i class="fas fa-star"></i> <?= number_format($movie['vote_average'], 1) ?>/10
                        </span>
                        <?php if ($ratingStats['count'] > 0): ?>
                            <span class="rating-badge ms-2">
                                <i class="fas fa-users"></i> <?= number_format($ratingStats['avg_rating'], 1) ?>/10
                                (<?= $ratingStats['count'] ?> user reviews)
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <?php foreach ($movie['genres'] as $genre): ?>
                            <span class="genre-badge"><?= htmlspecialchars($genre['name']) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!$isGuest): ?>
                    <div class="mt-4">
                        <button class="btn btn-danger action-btn <?= $userStatus['isFavorite'] ? 'active' : '' ?>" 
                                onclick="toggleFavorite(<?= $tmdbId ?>)">
                            <i class="fas fa-heart"></i> <?= $userStatus['isFavorite'] ? 'Remove from Favorites' : 'Add to Favorites' ?>
                        </button>
                        <button class="btn btn-info action-btn <?= $userStatus['inWatchLater'] ? 'active' : '' ?>" 
                                onclick="toggleWatchLater(<?= $tmdbId ?>)">
                            <i class="fas fa-clock"></i> <?= $userStatus['inWatchLater'] ? 'Remove from Watch Later' : 'Add to Watch Later' ?>
                        </button>
                        <button class="btn btn-warning action-btn" data-bs-toggle="modal" data-bs-target="#ratingModal">
                            <i class="fas fa-star"></i> <?= $userStatus['userRating'] ? 'Update Rating' : 'Rate Movie' ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Movie Information -->
        <div class="content-card">
            <h2 class="mb-4"><i class="fas fa-info-circle"></i> Overview</h2>
            <p class="lead"><?= nl2br(htmlspecialchars($movie['overview'])) ?></p>
            
            <hr class="my-4">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="movie-info-item">
                        <strong><i class="fas fa-calendar"></i> Release Date:</strong>
                        <?= date('F d, Y', strtotime($movie['release_date'])) ?>
                    </div>
                    <div class="movie-info-item">
                        <strong><i class="fas fa-clock"></i> Runtime:</strong>
                        <?= $movie['runtime'] ?> minutes
                    </div>
                    <div class="movie-info-item">
                        <strong><i class="fas fa-money-bill"></i> Budget:</strong>
                        <?= $movie['budget'] > 0 ? '$' . number_format($movie['budget']) : 'N/A' ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="movie-info-item">
                        <strong><i class="fas fa-chart-line"></i> Revenue:</strong>
                        <?= $movie['revenue'] > 0 ? '$' . number_format($movie['revenue']) : 'N/A' ?>
                    </div>
                    <div class="movie-info-item">
                        <strong><i class="fas fa-language"></i> Original Language:</strong>
                        <?= strtoupper($movie['original_language']) ?>
                    </div>
                    <div class="movie-info-item">
                        <strong><i class="fas fa-vote-yea"></i> Vote Count:</strong>
                        <?= number_format($movie['vote_count']) ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($movie['production_companies'])): ?>
                <hr class="my-4">
                <div class="movie-info-item">
                    <strong><i class="fas fa-building"></i> Production Companies:</strong>
                    <?php 
                    $companies = array_map(function($c) { return $c['name']; }, $movie['production_companies']);
                    echo htmlspecialchars(implode(', ', $companies));
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Trailer -->
        <?php if ($trailer): ?>
        <div class="content-card">
            <h2 class="mb-4"><i class="fas fa-play-circle"></i> Trailer</h2>
            <div class="video-container">
                <iframe src="<?= $trailer ?>" frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
        <?php endif; ?>

        <!-- Images Gallery -->
        <?php if (!empty($images['backdrops'])): ?>
        <div class="content-card">
            <h2 class="mb-4"><i class="fas fa-images"></i> Images</h2>
            <div class="image-gallery">
                <?php foreach (array_slice($images['backdrops'], 0, 12) as $image): ?>
                    <img src="https://image.tmdb.org/t/p/w500<?= $image['file_path'] ?>" 
                         alt="Movie Image" 
                         onclick="window.open('https://image.tmdb.org/t/p/original<?= $image['file_path'] ?>', '_blank')">
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Reviews -->
        <div class="content-card">
            <h2 class="mb-4"><i class="fas fa-comments"></i> User Reviews</h2>
            
            <?php if (empty($reviews)): ?>
                <p class="text-muted">No reviews yet. Be the first to review this movie!</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <strong><?= htmlspecialchars($review['username']) ?></strong>
                                <span class="ms-2 text-warning">
                                    <?php for ($i = 0; $i < floor($review['rating']); $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                    <?php if ($review['rating'] - floor($review['rating']) >= 0.5): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php endif; ?>
                                    <span class="text-dark"><?= number_format($review['rating'], 1) ?>/10</span>
                                </span>
                            </div>
                            <small class="text-muted"><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
                        </div>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($review['review'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Similar Movies -->
        <?php if (!empty($similarMovies)): ?>
        <div class="content-card">
            <h2 class="mb-4"><i class="fas fa-film"></i> Similar Movies</h2>
            <div class="row">
                <?php foreach ($similarMovies as $similar): ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                        <a href="movie-details.php?id=<?= $similar['id'] ?>" class="similar-movie-card">
                            <div class="card h-100">
                                <img src="<?= htmlspecialchars($similar['poster_path'] ?? 'https://via.placeholder.com/200x300?text=No+Image') ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($similar['title']) ?>"
                                     onerror="this.src='https://via.placeholder.com/200x300?text=No+Image'">
                                <div class="card-body p-2">
                                    <p class="card-text small text-center mb-1"><?= htmlspecialchars($similar['title']) ?></p>
                                    <p class="text-center mb-0 small">
                                        <i class="fas fa-star text-warning"></i> <?= number_format($similar['vote_average'], 1) ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Rating Modal -->
    <?php if (!$isGuest): ?>
    <div class="modal fade" id="ratingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rate "<?= htmlspecialchars($movie['title']) ?>"</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="ratingForm">
                        <div class="mb-3">
                            <label for="rating" class="form-label">Your Rating (1-10)</label>
                            <input type="number" class="form-control" id="rating" name="rating" 
                                   min="1" max="10" step="0.5" required
                                   value="<?= $userStatus['userRating'] ?? 5 ?>">
                        </div>
                        <div class="mb-3">
                            <label for="review" class="form-label">Your Review (Optional)</label>
                            <textarea class="form-control" id="review" name="review" rows="5"><?= htmlspecialchars($userStatus['userReview'] ?? '') ?></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitRating()">Submit Rating</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const tmdbId = <?= $tmdbId ?>;

        function toggleFavorite(movieId) {
            fetch('api/favorites.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'toggle',
                    tmdb_id: movieId,
                    movie_title: '<?= addslashes($movie['title']) ?>',
                    poster_url: '<?= addslashes($movie['poster_path'] ?? '') ?>',
                    release_year: <?= date('Y', strtotime($movie['release_date'])) ?>,
                    category: '<?= addslashes($movie['genres'][0]['name'] ?? '') ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function toggleWatchLater(movieId) {
            fetch('api/watch-later.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'add',
                    tmdb_id: movieId,
                    movie_title: '<?= addslashes($movie['title']) ?>',
                    poster_url: '<?= addslashes($movie['poster_path'] ?? '') ?>',
                    release_year: <?= date('Y', strtotime($movie['release_date'])) ?>,
                    category: '<?= addslashes($movie['genres'][0]['name'] ?? '') ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function submitRating() {
            const rating = document.getElementById('rating').value;
            const review = document.getElementById('review').value;

            fetch('api/ratings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'add',
                    tmdb_id: tmdbId,
                    rating: parseFloat(rating),
                    review: review,
                    movie_title: '<?= addslashes($movie['title']) ?>',
                    poster_url: '<?= addslashes($movie['poster_path'] ?? '') ?>',
                    release_year: <?= date('Y', strtotime($movie['release_date'])) ?>,
                    category: '<?= addslashes($movie['genres'][0]['name'] ?? '') ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
