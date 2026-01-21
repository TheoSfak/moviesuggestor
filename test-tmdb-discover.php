<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/TMDBService.php';

use MovieSuggestor\TMDBService;

echo "Testing TMDB discoverMovies...\n\n";

$tmdb = new TMDBService();

$filters = [
    'page' => 1
];

echo "Calling discoverMovies with filters: " . json_encode($filters) . "\n\n";

$result = $tmdb->discoverMovies($filters);

echo "Result:\n";
print_r($result);

if (!$result['success']) {
    echo "\nERROR: " . ($result['error'] ?? 'Unknown error') . "\n";
}
