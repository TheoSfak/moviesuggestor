<?php

/**
 * TMDB Search API Endpoint
 * Handles searching TMDB for movies and returning results
 * 
 * GET Parameters:
 * - query: Search query string
 * - page: Page number (default: 1)
 * - discover: Use discover endpoint (1 or 0)
 * - genre: Genre ID for discovery
 * - year: Release year filter
 * - popular: Get popular movies (1 or 0)
 */

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Security.php';

use MovieSuggestor\TMDBService;
use MovieSuggestor\Security;

// Enable error logging but hide errors from output
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Apply rate limiting (10 requests per 60 seconds per IP)
// TMDB search is public but should be rate-limited to prevent abuse
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitKey = 'tmdb_search_' . $clientIp;

if (!Security::checkRateLimit($rateLimitKey, 10, 60)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'Too many requests. Please wait a moment before searching again.'
    ]);
    exit;
}

try {
    // Initialize TMDB service
    $tmdb = new TMDBService();
    
    // Check if API is configured
    if (!$tmdb->isConfigured()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'TMDB API not configured. Please set TMDB_API_KEY environment variable.',
            'setup_url' => 'https://www.themoviedb.org/settings/api'
        ]);
        exit;
    }

    // Get request parameters
    $query = $_GET['query'] ?? '';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $discover = !empty($_GET['discover']);
    $popular = !empty($_GET['popular']);
    $trailerRequest = isset($_GET['trailer']) ? (int)$_GET['trailer'] : null;

    $result = null;
    
    // Handle trailer request
    if ($trailerRequest) {
        $trailerUrl = $tmdb->getMovieTrailer($trailerRequest);
        
        if ($trailerUrl) {
            echo json_encode([
                'success' => true,
                'trailer_url' => $trailerUrl,
                'tmdb_id' => $trailerRequest
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No trailer available for this movie'
            ]);
        }
        exit;
    }

    // Handle popular movies request
    if ($popular) {
        $result = $tmdb->getPopularMovies($page);
        
        // Check for API errors
        if (!$result['success']) {
            http_response_code(500);
            echo json_encode($result);
            exit;
        }
        
        // getPopularMovies already returns formatted results
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    // Handle discover request
    elseif ($discover) {
        $filters = [];
        
        if (!empty($_GET['genre'])) {
            $filters['genre'] = (int)$_GET['genre'];
        }
        
        if (!empty($_GET['year'])) {
            $filters['year'] = (int)$_GET['year'];
        }
        
        if (!empty($_GET['min_rating'])) {
            $filters['min_rating'] = (float)$_GET['min_rating'];
        }
        
        if (!empty($_GET['sort_by'])) {
            $filters['sort_by'] = $_GET['sort_by'];
        }
        
        $filters['page'] = $page;
        
        $result = $tmdb->discoverMovies($filters);
    }
    // Handle search request
    else {
        if (empty($query)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Search query is required'
            ]);
            exit;
        }
        
        $result = $tmdb->searchMovies($query, $page);
    }

    // Check for API errors
    if (!$result['success']) {
        http_response_code(500);
        echo json_encode($result);
        exit;
    }

    // Process and enhance results
    $data = $result['data'];
    $enhancedResults = [];

    if (isset($data['results'])) {
        foreach ($data['results'] as $movie) {
            // Map genre IDs to Greek categories
            $genreIds = $movie['genre_ids'] ?? [];
            $category = $tmdb->mapGenresToCategory($genreIds);
            
            // Extract release year
            $releaseYear = null;
            if (!empty($movie['release_date'])) {
                $releaseYear = (int)substr($movie['release_date'], 0, 4);
            }
            
            // Build enhanced result
            $enhancedResults[] = [
                'tmdb_id' => $movie['id'],
                'title' => $movie['title'] ?? 'N/A',
                'original_title' => $movie['original_title'] ?? '',
                'overview' => $movie['overview'] ?? '',
                'release_date' => $movie['release_date'] ?? null,
                'release_year' => $releaseYear,
                'vote_average' => round($movie['vote_average'] ?? 0, 1),
                'vote_count' => $movie['vote_count'] ?? 0,
                'popularity' => $movie['popularity'] ?? 0,
                'poster_path' => $movie['poster_path'],
                'poster_url' => $tmdb->getPosterUrl($movie['poster_path'] ?? null),
                'backdrop_path' => $movie['backdrop_path'],
                'backdrop_url' => $tmdb->getBackdropUrl($movie['backdrop_path'] ?? null),
                'genre_ids' => $genreIds,
                'category' => $category,
                'adult' => $movie['adult'] ?? false,
                'original_language' => $movie['original_language'] ?? ''
            ];
        }
    }

    // Return enhanced results
    echo json_encode([
        'success' => true,
        'page' => $data['page'] ?? 1,
        'total_pages' => $data['total_pages'] ?? 1,
        'total_results' => $data['total_results'] ?? 0,
        'results' => $enhancedResults
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    error_log("TMDB Search Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred'
    ]);
}
