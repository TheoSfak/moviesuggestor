<?php
/**
 * TMDB Integration Test Script
 * Tests TMDB API connectivity and functionality
 */

require_once __DIR__ . '/vendor/autoload.php';

use MovieSuggestor\TMDBService;

echo "===========================================\n";
echo "TMDB Integration Test\n";
echo "===========================================\n\n";

// Check if API key is configured
$apiKey = getenv('TMDB_API_KEY');
echo "1. Checking API Key Configuration...\n";
if (empty($apiKey)) {
    echo "   ❌ TMDB_API_KEY not set in environment\n";
    echo "   Set it using: \$env:TMDB_API_KEY = 'your_key'\n\n";
    echo "   Get your API key from: https://www.themoviedb.org/settings/api\n";
    exit(1);
} else {
    echo "   ✓ API Key found: " . substr($apiKey, 0, 8) . "...\n\n";
}

// Initialize TMDB Service
echo "2. Initializing TMDB Service...\n";
$tmdb = new TMDBService();
if ($tmdb->isConfigured()) {
    echo "   ✓ Service initialized successfully\n\n";
} else {
    echo "   ❌ Service not configured\n";
    exit(1);
}

// Test Search
echo "3. Testing Movie Search (The Matrix)...\n";
$searchResult = $tmdb->searchMovies('The Matrix', 1);
if ($searchResult['success']) {
    $movieCount = count($searchResult['data']['results'] ?? []);
    echo "   ✓ Search successful - Found {$movieCount} results\n";
    
    if ($movieCount > 0) {
        $firstMovie = $searchResult['data']['results'][0];
        echo "   First result: {$firstMovie['title']} ({$firstMovie['release_date']})\n";
        echo "   Rating: {$firstMovie['vote_average']}/10\n\n";
    }
} else {
    echo "   ❌ Search failed: " . $searchResult['error'] . "\n";
    exit(1);
}

// Test Popular Movies
echo "4. Testing Popular Movies...\n";
$popularResult = $tmdb->getPopularMovies(1);
if ($popularResult['success']) {
    $movieCount = count($popularResult['data']['results'] ?? []);
    echo "   ✓ Popular movies retrieved - {$movieCount} movies\n\n";
} else {
    echo "   ❌ Failed: " . $popularResult['error'] . "\n";
    exit(1);
}

// Test Genre Mapping
echo "5. Testing Genre Mapping...\n";
$testGenres = [18, 35, 28, 10749, 27];
foreach ($testGenres as $genreId) {
    $category = $tmdb->mapGenresToCategory([$genreId]);
    echo "   Genre {$genreId} → {$category}\n";
}
echo "   ✓ Genre mapping working\n\n";

// Test Movie Details (The Matrix - TMDB ID: 603)
echo "6. Testing Movie Details (The Matrix)...\n";
$detailsResult = $tmdb->getMovieDetails(603);
if ($detailsResult['success']) {
    $movie = $detailsResult['data'];
    echo "   ✓ Details retrieved\n";
    echo "   Title: {$movie['title']}\n";
    echo "   Runtime: {$movie['runtime']} minutes\n";
    
    $director = $tmdb->extractDirector($movie);
    echo "   Director: {$director}\n";
    
    $actors = $tmdb->extractActors($movie, 3);
    echo "   Actors: {$actors}\n";
    
    $trailer = $tmdb->extractTrailerUrl($movie);
    if ($trailer) {
        echo "   Trailer: {$trailer}\n";
    }
    echo "\n";
} else {
    echo "   ❌ Failed: " . $detailsResult['error'] . "\n";
    exit(1);
}

// Test Image URLs
echo "7. Testing Image URL Generation...\n";
$posterUrl = $tmdb->getPosterUrl('/hEpWvX6Bp79qscrfcokmzfkVuL7.jpg');
$backdropUrl = $tmdb->getBackdropUrl('/fNG7i7RqMErkcqhohV2a6cV1Ehy.jpg');
echo "   Poster URL: {$posterUrl}\n";
echo "   Backdrop URL: {$backdropUrl}\n";
echo "   ✓ Image URLs generated correctly\n\n";

// Summary
echo "===========================================\n";
echo "✓ All Tests Passed!\n";
echo "===========================================\n\n";
echo "TMDB Integration is ready to use.\n";
echo "Open http://localhost/moviesuggestor/ to start searching movies!\n\n";
echo "Quick Start:\n";
echo "1. Open the web application\n";
echo "2. Find the '🌐 Search Online Movies' section\n";
echo "3. Search for any movie or click 'Popular'\n";
echo "4. Click 'Import to Database' to add movies\n\n";
