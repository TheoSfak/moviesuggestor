<?php

/**
 * Favorites API Endpoint
 * 
 * RESTful API for managing user favorite movies.
 * 
 * Supported operations:
 * - GET    /api/favorites.php?user_id={id} - List favorites
 * - POST   /api/favorites.php - Add to favorites
 * - DELETE /api/favorites.php - Remove from favorites
 * - OPTIONS - CORS preflight
 * 
 * @package MovieSuggestor
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/FavoritesRepository.php';

use MovieSuggestor\Database;
use MovieSuggestor\FavoritesRepository;

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON response headers
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 3600');

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Send JSON response
 * 
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 * @param string|null $error Error message if any
 */
function sendResponse($data = null, int $statusCode = 200, ?string $error = null): void
{
    http_response_code($statusCode);
    
    $response = [];
    
    if ($error !== null) {
        $response['success'] = false;
        $response['error'] = $error;
    } else {
        $response['success'] = true;
        if ($data !== null) {
            $response['data'] = $data;
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Validate required fields
 * 
 * @param array $data Input data
 * @param array $required Required field names
 * @return bool True if all required fields present
 */
function validateRequired(array $data, array $required): bool
{
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            return false;
        }
    }
    return true;
}

/**
 * Get JSON input from request body
 * 
 * @return array|null Parsed JSON data or null on error
 */
function getJsonInput(): ?array
{
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return null;
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $data;
}

try {
    // Initialize repository
    $db = new Database();
    $favoritesRepo = new FavoritesRepository($db);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // List favorites for a user
            if (!isset($_GET['user_id'])) {
                sendResponse(null, 400, 'user_id parameter is required');
            }
            
            $userId = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);
            if ($userId === false || $userId <= 0) {
                sendResponse(null, 400, 'user_id must be a positive integer');
            }
            
            $favorites = $favoritesRepo->getFavorites($userId);
            $count = $favoritesRepo->getFavoritesCount($userId);
            
            sendResponse([
                'favorites' => $favorites,
                'count' => $count
            ]);
            break;
            
        case 'POST':
            // Add to favorites
            $input = getJsonInput();
            
            if ($input === null) {
                sendResponse(null, 400, 'Invalid JSON input');
            }
            
            if (!validateRequired($input, ['user_id', 'tmdb_id'])) {
                sendResponse(null, 400, 'user_id and tmdb_id are required');
            }
            
            $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
            $tmdbId = filter_var($input['tmdb_id'], FILTER_VALIDATE_INT);
            
            if ($userId === false || $userId <= 0) {
                sendResponse(null, 400, 'user_id must be a positive integer');
            }
            
            if ($tmdbId === false || $tmdbId <= 0) {
                sendResponse(null, 400, 'tmdb_id must be a positive integer');
            }
            
            // Extract movie snapshot data
            $movieData = [
                'title' => $input['title'] ?? null,
                'poster_url' => $input['poster_url'] ?? null,
                'release_year' => isset($input['release_year']) ? filter_var($input['release_year'], FILTER_VALIDATE_INT) : null,
                'category' => $input['category'] ?? null
            ];
            
            // Check if already favorited
            $isFavorite = $favoritesRepo->isFavorite($userId, $tmdbId);
            if ($isFavorite) {
                sendResponse(['message' => 'Movie is already in favorites'], 200);
            }
            
            $result = $favoritesRepo->addToFavorites($userId, $tmdbId, $movieData);
            
            if ($result) {
                sendResponse(['message' => 'Movie added to favorites'], 201);
            } else {
                sendResponse(null, 500, 'Failed to add movie to favorites');
            }
            break;
            
        case 'DELETE':
            // Remove from favorites
            $input = getJsonInput();
            
            if ($input === null) {
                sendResponse(null, 400, 'Invalid JSON input');
            }
            
            if (!validateRequired($input, ['user_id', 'tmdb_id'])) {
                sendResponse(null, 400, 'user_id and tmdb_id are required');
            }
            
            $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
            $tmdbId = filter_var($input['tmdb_id'], FILTER_VALIDATE_INT);
            
            if ($userId === false || $userId <= 0) {
                sendResponse(null, 400, 'user_id must be a positive integer');
            }
            
            if ($tmdbId === false || $tmdbId <= 0) {
                sendResponse(null, 400, 'tmdb_id must be a positive integer');
            }
            
            $result = $favoritesRepo->removeFromFavorites($userId, $tmdbId);
            
            if ($result) {
                sendResponse(['message' => 'Movie removed from favorites'], 200);
            } else {
                sendResponse(null, 500, 'Failed to remove movie from favorites');
            }
            break;
            
        default:
            sendResponse(null, 405, 'Method not allowed');
            break;
    }
    
} catch (\InvalidArgumentException $e) {
    sendResponse(null, 400, $e->getMessage());
} catch (\RuntimeException $e) {
    sendResponse(null, 500, $e->getMessage());
} catch (\Exception $e) {
    error_log('Favorites API Error: ' . $e->getMessage());
    sendResponse(null, 500, 'An unexpected error occurred');
}
