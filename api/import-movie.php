<?php

/**
 * TMDB Movie Import API Endpoint
 * Imports a movie from TMDB into the local database
 * 
 * POST Parameters (JSON):
 * - tmdb_id: TMDB movie ID (required)
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

use MovieSuggestor\Database;
use MovieSuggestor\TMDBService;

// Enable error logging but hide errors from output
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

try {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON input'
        ]);
        exit;
    }

    // Validate required parameters
    if (empty($input['tmdb_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'tmdb_id is required'
        ]);
        exit;
    }

    $tmdbId = (int)$input['tmdb_id'];

    // Initialize services
    $tmdb = new TMDBService();
    $database = new Database();
    $db = $database->connect();

    // Check if API is configured
    if (!$tmdb->isConfigured()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'TMDB API not configured'
        ]);
        exit;
    }

    // Check if movie already exists in database
    $checkStmt = $db->prepare("SELECT id, title FROM movies WHERE title = :title LIMIT 1");
    
    // First, get basic movie details to check title
    $basicResult = $tmdb->searchMovies("", 1); // We'll get details directly
    
    // Get detailed movie information from TMDB
    $result = $tmdb->getMovieDetails($tmdbId);

    if (!$result['success']) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
        exit;
    }

    $movie = $result['data'];

    // Check for duplicate by title
    $checkStmt->execute(['title' => $movie['title']]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Movie already exists in database',
            'existing_id' => $existing['id'],
            'existing_title' => $existing['title']
        ]);
        exit;
    }

    // Map TMDB data to local schema
    $genreIds = array_map(fn($g) => $g['id'], $movie['genres'] ?? []);
    $category = $tmdb->mapGenresToCategory($genreIds);
    
    $releaseYear = null;
    if (!empty($movie['release_date'])) {
        $releaseYear = (int)substr($movie['release_date'], 0, 4);
    }

    $director = $tmdb->extractDirector($movie);
    $actors = $tmdb->extractActors($movie);
    $trailerUrl = $tmdb->extractTrailerUrl($movie);
    $posterUrl = $tmdb->getPosterUrl($movie['poster_path'] ?? null);
    $backdropUrl = $tmdb->getBackdropUrl($movie['backdrop_path'] ?? null);
    
    // Use TMDB vote_average as the score
    $score = round($movie['vote_average'] ?? 0, 1);
    
    // Get runtime
    $runtime = $movie['runtime'] ?? null;

    // Prepare insert statement
    $sql = "INSERT INTO movies (
        title, 
        category, 
        score, 
        trailer_url, 
        description,
        release_year,
        director,
        actors,
        runtime_minutes,
        poster_url,
        backdrop_url,
        imdb_rating,
        votes_count,
        created_at,
        updated_at
    ) VALUES (
        :title,
        :category,
        :score,
        :trailer_url,
        :description,
        :release_year,
        :director,
        :actors,
        :runtime_minutes,
        :poster_url,
        :backdrop_url,
        :imdb_rating,
        :votes_count,
        NOW(),
        NOW()
    )";

    $stmt = $db->prepare($sql);
    
    $params = [
        'title' => $movie['title'] ?? 'Unknown',
        'category' => $category,
        'score' => $score,
        'trailer_url' => $trailerUrl,
        'description' => $movie['overview'] ?? '',
        'release_year' => $releaseYear,
        'director' => $director,
        'actors' => $actors,
        'runtime_minutes' => $runtime,
        'poster_url' => $posterUrl,
        'backdrop_url' => $backdropUrl,
        'imdb_rating' => $score, // Use TMDB rating as IMDB rating
        'votes_count' => $movie['vote_count'] ?? 0
    ];

    $stmt->execute($params);
    $insertedId = $db->lastInsertId();

    // Return success response with inserted movie data
    echo json_encode([
        'success' => true,
        'message' => 'Movie imported successfully',
        'movie_id' => $insertedId,
        'movie' => [
            'id' => $insertedId,
            'title' => $params['title'],
            'category' => $params['category'],
            'score' => $params['score'],
            'release_year' => $params['release_year'],
            'director' => $params['director'],
            'actors' => $params['actors'],
            'runtime_minutes' => $params['runtime_minutes'],
            'poster_url' => $params['poster_url'],
            'backdrop_url' => $params['backdrop_url'],
            'description' => $params['description'],
            'trailer_url' => $params['trailer_url']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    error_log("Database error during movie import: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Import error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected error occurred'
    ]);
}
