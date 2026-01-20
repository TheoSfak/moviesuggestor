<?php

/**
 * Watch Later API Endpoint
 * 
 * RESTful API for managing user watch later list.
 * 
 * Supported operations:
 * - GET    /api/watch-later.php?user_id={id}[&include_watched=1] - List watch later movies
 * - POST   /api/watch-later.php - Add to watch later
 * - DELETE /api/watch-later.php - Remove from watch later
 * - PATCH  /api/watch-later.php - Mark as watched
 * - OPTIONS - CORS preflight
 * 
 * @package MovieSuggestor
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/WatchLaterRepository.php';

use MovieSuggestor\Database;
use MovieSuggestor\WatchLaterRepository;

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON response headers
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, PATCH, OPTIONS');
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
    $pdo = $db->connect();
    $watchLaterRepo = new WatchLaterRepository($pdo);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // List watch later movies for a user
            if (!isset($_GET['user_id'])) {
                sendResponse(null, 400, 'user_id parameter is required');
            }
            
            $userId = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);
            if ($userId === false || $userId <= 0) {
                sendResponse(null, 400, 'user_id must be a positive integer');
            }
            
            $includeWatched = isset($_GET['include_watched']) && $_GET['include_watched'] === '1';
            
            $watchLater = $watchLaterRepo->getWatchLater($userId, $includeWatched);
            $unwatchedCount = $watchLaterRepo->getUnwatchedCount($userId);
            
            sendResponse([
                'movies' => $watchLater,
                'count' => count($watchLater),
                'unwatched_count' => $unwatchedCount
            ]);
            break;
            
        case 'POST':
            // Add to watch later
            $input = getJsonInput();
            
            if ($input === null) {
                sendResponse(null, 400, 'Invalid JSON input');
            }
            
            if (!validateRequired($input, ['user_id', 'movie_id'])) {
                sendResponse(null, 400, 'user_id and movie_id are required');
            }
            
            $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
            $movieId = filter_var($input['movie_id'], FILTER_VALIDATE_INT);
            
            if ($userId === false || $userId <= 0) {
                sendResponse(null, 400, 'user_id must be a positive integer');
            }
            
            if ($movieId === false || $movieId <= 0) {
                sendResponse(null, 400, 'movie_id must be a positive integer');
            }
            
            // Check if already in watch later
            $isInList = $watchLaterRepo->isInWatchLater($userId, $movieId);
            if ($isInList) {
                sendResponse(['message' => 'Movie is already in watch later list'], 200);
            }
            
            $result = $watchLaterRepo->addToWatchLater($userId, $movieId);
            
            if ($result) {
                sendResponse(['message' => 'Movie added to watch later'], 201);
            } else {
                sendResponse(null, 500, 'Failed to add movie to watch later');
            }
            break;
            
        case 'DELETE':
            // Remove from watch later
            $input = getJsonInput();
            
            if ($input === null) {
                sendResponse(null, 400, 'Invalid JSON input');
            }
            
            if (!validateRequired($input, ['user_id', 'movie_id'])) {
                sendResponse(null, 400, 'user_id and movie_id are required');
            }
            
            $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
            $movieId = filter_var($input['movie_id'], FILTER_VALIDATE_INT);
            
            if ($userId === false || $userId <= 0) {
                sendResponse(null, 400, 'user_id must be a positive integer');
            }
            
            if ($movieId === false || $movieId <= 0) {
                sendResponse(null, 400, 'movie_id must be a positive integer');
            }
            
            $result = $watchLaterRepo->removeFromWatchLater($userId, $movieId);
            
            if ($result) {
                sendResponse(['message' => 'Movie removed from watch later'], 200);
            } else {
                sendResponse(null, 500, 'Failed to remove movie from watch later');
            }
            break;
            
        case 'PATCH':
            // Mark as watched
            $input = getJsonInput();
            
            if ($input === null) {
                sendResponse(null, 400, 'Invalid JSON input');
            }
            
            if (!validateRequired($input, ['user_id', 'movie_id'])) {
                sendResponse(null, 400, 'user_id and movie_id are required');
            }
            
            $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
            $movieId = filter_var($input['movie_id'], FILTER_VALIDATE_INT);
            
            if ($userId === false || $userId <= 0) {
                sendResponse(null, 400, 'user_id must be a positive integer');
            }
            
            if ($movieId === false || $movieId <= 0) {
                sendResponse(null, 400, 'movie_id must be a positive integer');
            }
            
            $result = $watchLaterRepo->markAsWatched($userId, $movieId);
            
            if ($result) {
                sendResponse(['message' => 'Movie marked as watched'], 200);
            } else {
                sendResponse(null, 500, 'Failed to mark movie as watched');
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
    error_log('Watch Later API Error: ' . $e->getMessage());
    sendResponse(null, 500, 'An unexpected error occurred');
}
