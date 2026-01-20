#!/usr/bin/env php
<?php
/**
 * Database validation script
 * Run this to check if database connection works before starting the app
 */

require_once __DIR__ . '/vendor/autoload.php';

use MovieSuggestor\Database;
use MovieSuggestor\MovieRepository;

echo "🔍 Movie Suggestor - Database Validation\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Database Connection
echo "1. Testing database connection...\n";
try {
    $database = new Database();
    $db = $database->connect();
    echo "   ✅ Database connection successful\n\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Query Execution
echo "2. Testing query execution...\n";
try {
    $repository = new MovieRepository($database);
    $categories = $repository->getAllCategories();
    echo "   ✅ Query execution successful\n";
    echo "   Found " . count($categories) . " categories\n\n";
} catch (Exception $e) {
    echo "   ❌ Query execution failed\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Data Retrieval
echo "3. Testing data retrieval...\n";
try {
    $movies = $repository->findByFilters();
    echo "   ✅ Data retrieval successful\n";
    echo "   Found " . count($movies) . " movies\n\n";
    
    if (count($movies) > 0) {
        $movie = $movies[0];
        echo "   Sample movie:\n";
        echo "   - Title: " . $movie['title'] . "\n";
        echo "   - Category: " . $movie['category'] . "\n";
        echo "   - Score: " . $movie['score'] . "\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Data retrieval failed\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Filtering
echo "4. Testing filtering...\n";
try {
    $filteredMovies = $repository->findByFilters('Action', 8.0);
    echo "   ✅ Filtering successful\n";
    echo "   Found " . count($filteredMovies) . " Action movies with score >= 8.0\n\n";
} catch (Exception $e) {
    echo "   ❌ Filtering failed\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo str_repeat("=", 50) . "\n";
echo "✅ All validation checks passed!\n";
echo "🚀 You can now run the application\n\n";
exit(0);
