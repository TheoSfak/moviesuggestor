<?php
/**
 * API Endpoint for Movie Suggestor AJAX Operations
 * 
 * Handles asynchronous requests for:
 * - Adding/removing favorites
 * - Adding/removing from watch later
 * - Submitting/updating ratings
 * 
 * Returns JSON responses
 */

// Security headers
header('Content-Type: application/json');
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Start session
session_start();

// Check if user is logged in (in production, implement proper authentication)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use MovieSuggestor\Database;
use MovieSuggestor\FavoritesRepository;
use MovieSuggestor\WatchLaterRepository;
use MovieSuggestor\RatingRepository;

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';
$userId = (int)($input['user_id'] ?? 0);
$movieId = (int)($input['movie_id'] ?? 0);

// Validate user ID matches session
if ($userId !== $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'User ID mismatch']);
    exit;
}

// Validate movie ID
if ($movieId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid movie ID']);
    exit;
}

try {
    $database = new Database();
    
    switch ($action) {
        case 'favorite':
            handleFavorite($database, $input, $userId, $movieId);
            break;
            
        case 'watchlater':
            handleWatchLater($database, $input, $userId, $movieId);
            break;
            
        case 'rate':
            handleRating($database, $input, $userId, $movieId);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Handle favorite add/remove operations
 */
function handleFavorite(Database $database, array $input, int $userId, int $movieId): void
{
    $operation = $input['operation'] ?? '';
    $repo = new FavoritesRepository($database);
    
    if ($operation === 'add') {
        $result = $repo->addToFavorites($userId, $movieId);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Added to favorites' : 'Failed to add to favorites'
        ]);
        
    } elseif ($operation === 'remove') {
        $result = $repo->removeFromFavorites($userId, $movieId);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Removed from favorites' : 'Failed to remove from favorites'
        ]);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid operation']);
    }
}

/**
 * Handle watch later add/remove operations
 */
function handleWatchLater(Database $database, array $input, int $userId, int $movieId): void
{
    $operation = $input['operation'] ?? '';
    $repo = new WatchLaterRepository($database->connect());
    
    if ($operation === 'add') {
        $result = $repo->addToWatchLater($userId, $movieId);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Added to watch later' : 'Failed to add to watch later'
        ]);
        
    } elseif ($operation === 'remove') {
        $result = $repo->removeFromWatchLater($userId, $movieId);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Removed from watch later' : 'Failed to remove from watch later'
        ]);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid operation']);
    }
}

/**
 * Handle movie rating add/update operations
 */
function handleRating(Database $database, array $input, int $userId, int $movieId): void
{
    $rating = (float)($input['rating'] ?? 0);
    
    if ($rating < 1 || $rating > 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 10']);
        return;
    }
    
    $repo = new RatingRepository($database);
    
    // Check if user has already rated this movie
    $existingRating = $repo->getUserRating($userId, $movieId);
    
    if ($existingRating) {
        // Update existing rating
        $result = $repo->updateRating($userId, $movieId, $rating);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Rating updated' : 'Failed to update rating',
            'rating' => $rating
        ]);
    } else {
        // Add new rating
        $result = $repo->addRating($userId, $movieId, $rating);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Rating submitted' : 'Failed to submit rating',
            'rating' => $rating
        ]);
    }
}
