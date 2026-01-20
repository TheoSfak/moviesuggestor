<?php

/**
 * Ratings API Endpoint
 * 
 * RESTful API for managing movie ratings and reviews.
 * 
 * Supported operations:
 * - GET    /api/ratings.php?user_id={id}&movie_id={id} - Get specific rating
 * - GET    /api/ratings.php?user_id={id} - Get all user ratings
 * - GET    /api/ratings.php?movie_id={id} - Get movie rating statistics
 * - POST   /api/ratings.php - Add new rating
 * - PUT    /api/ratings.php - Update existing rating
 * - DELETE /api/ratings.php - Delete rating
 * - OPTIONS - CORS preflight
 * 
 * @package MovieSuggestor
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/RatingRepository.php';

use MovieSuggestor\Database;
use MovieSuggestor\RatingRepository;

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON response headers
header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    $ratingRepo = new RatingRepository($db);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get rating(s)
            if (isset($_GET['user_id']) && isset($_GET['movie_id'])) {
                // Get specific user's rating for a movie
                $userId = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);
                $movieId = filter_var($_GET['movie_id'], FILTER_VALIDATE_INT);
                
                if ($userId === false || $userId <= 0) {
                    sendResponse(null, 400, 'user_id must be a positive integer');
                }
                
                if ($movieId === false || $movieId <= 0) {
                    sendResponse(null, 400, 'movie_id must be a positive integer');
                }
                
                $rating = $ratingRepo->getUserRating($userId, $movieId);
                
                if ($rating === null) {
                    sendResponse(null, 404, 'Rating not found');
                }
                
                sendResponse(['rating' => $rating]);
                
            } elseif (isset($_GET['user_id'])) {
                // Get all ratings by a user - not currently supported
                sendResponse(null, 501, 'User ratings list not yet implemented');
                
            } elseif (isset($_GET['movie_id'])) {
                // Get rating statistics for a movie
                $movieId = filter_var($_GET['movie_id'], FILTER_VALIDATE_INT);
                
                if ($movieId === false || $movieId <= 0) {
                    sendResponse(null, 400, 'movie_id must be a positive integer');
                }
                
                $avgRating = $ratingRepo->getAverageRating($movieId);
                $ratingCount = $ratingRepo->getRatingsCount($movieId);
                $ratings = $ratingRepo->getAllRatings($movieId);
                
                sendResponse([
                    'average_rating' => $avgRating,
                    'rating_count' => $ratingCount,
                    'ratings' => $ratings
                ]);
                
            } else {
                sendResponse(null, 400, 'user_id or movie_id parameter is required');
            }
            break;
            
        case 'POST':
            // Add new rating
            $input = getJsonInput();
            
            if ($input === null) {
                sendResponse(null, 400, 'Invalid JSON input');
            }
            
            if (!validateRequired($input, ['user_id', 'movie_id', 'rating'])) {
                sendResponse(null, 400, 'user_id, movie_id, and rating are required');
            }
            
            $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
            $movieId = filter_var($input['movie_id'], FILTER_VALIDATE_INT);
            $rating = filter_var($input['rating'], FILTER_VALIDATE_FLOAT);
            $review = isset($input['review']) ? trim($input['review']) : null;
            
            if ($userId === false || $userId <= 0) {
                sendResponse(null, 400, 'user_id must be a positive integer');
            }
            
            if ($movieId === false || $movieId <= 0) {
                sendResponse(null, 400, 'movie_id must be a positive integer');
            }
            
            if ($rating === false) {
                sendResponse(null, 400, 'rating must be a valid number');
            }
            
            if ($rating < 1 || $rating > 10) {
                sendResponse(null, 400, 'rating must be between 1 and 10');
            }
            
            // Handle empty review string
            if ($review !== null && $review === '') {
                $review = null;
            }
            
            // Check if rating already exists
            $existingRating = $ratingRepo->getUserRating($userId, $movieId);
            if ($existingRating !== null) {
                sendResponse(null, 409, 'Rating already exists. Use PUT to update.');
            }
            
            $result = $ratingRepo->addRating($userId, $movieId, $rating, $review);
            
            if ($result) {
                sendResponse(['message' => 'Rating added successfully'], 201);
            } else {
                sendResponse(null, 500, 'Failed to add rating');
            }
            break;
            
        case 'PUT':
            // Update existing rating
            $input = getJsonInput();
            
            if ($input === null) {
                sendResponse(null, 400, 'Invalid JSON input');
            }
            
            if (!validateRequired($input, ['user_id', 'movie_id', 'rating'])) {
                sendResponse(null, 400, 'user_id, movie_id, and rating are required');
            }
            
            $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
            $movieId = filter_var($input['movie_id'], FILTER_VALIDATE_INT);
            $rating = filter_var($input['rating'], FILTER_VALIDATE_FLOAT);
            $review = isset($input['review']) ? trim($input['review']) : null;
            
            if ($userId === false || $userId <= 0) {
                sendResponse(null, 400, 'user_id must be a positive integer');
            }
            
            if ($movieId === false || $movieId <= 0) {
                sendResponse(null, 400, 'movie_id must be a positive integer');
            }
            
            if ($rating === false) {
                sendResponse(null, 400, 'rating must be a valid number');
            }
            
            if ($rating < 1 || $rating > 10) {
                sendResponse(null, 400, 'rating must be between 1 and 10');
            }
            
            // Handle empty review string
            if ($review !== null && $review === '') {
                $review = null;
            }
            
            $result = $ratingRepo->updateRating($userId, $movieId, $rating, $review);
            
            if ($result) {
                sendResponse(['message' => 'Rating updated successfully'], 200);
            } else {
                sendResponse(null, 500, 'Failed to update rating');
            }
            break;
            
        case 'DELETE':
            // Delete rating
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
            
            $result = $ratingRepo->deleteRating($userId, $movieId);
            
            if ($result) {
                sendResponse(['message' => 'Rating deleted successfully'], 200);
            } else {
                sendResponse(null, 500, 'Failed to delete rating');
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
    error_log('Ratings API Error: ' . $e->getMessage());
    sendResponse(null, 500, 'An unexpected error occurred');
}
