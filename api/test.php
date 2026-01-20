<?php

/**
 * API Test Script
 * 
 * Quick test to verify all API endpoints are accessible and responding.
 * Run this from command line: php api/test.php
 * 
 * @package MovieSuggestor
 */

echo "=====================================\n";
echo "Movie Suggestor API Test Script\n";
echo "=====================================\n\n";

$baseUrl = 'http://localhost/moviesuggestor/api';
$testUserId = 1;
$testMovieId = 1;

/**
 * Test an API endpoint
 */
function testEndpoint(string $name, string $url, string $method = 'GET', ?array $data = null): void
{
    echo "Testing: $name\n";
    echo "URL: $url\n";
    echo "Method: $method\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $httpCode\n";
    
    if ($response) {
        $decoded = json_decode($response, true);
        if ($decoded) {
            echo "Response: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Response: $response\n";
        }
    }
    
    echo str_repeat("-", 50) . "\n\n";
}

// Check if curl extension is available
if (!function_exists('curl_init')) {
    echo "⚠️  Warning: cURL extension not available.\n";
    echo "   Tests require cURL to run.\n";
    echo "   API endpoints are still functional via web browser or other HTTP clients.\n\n";
    exit(0);
}

echo "🧪 Running API Tests...\n\n";

// Test 1: API Documentation
echo "1. Checking API Documentation\n";
testEndpoint(
    'API Index',
    $baseUrl . '/',
    'GET'
);

// Test 2: Favorites
echo "2. Testing Favorites Endpoint\n";
testEndpoint(
    'Get Favorites',
    $baseUrl . "/favorites.php?user_id=$testUserId",
    'GET'
);

// Test 3: Watch Later
echo "3. Testing Watch Later Endpoint\n";
testEndpoint(
    'Get Watch Later',
    $baseUrl . "/watch-later.php?user_id=$testUserId",
    'GET'
);

// Test 4: Ratings
echo "4. Testing Ratings Endpoint\n";
testEndpoint(
    'Get Movie Ratings',
    $baseUrl . "/ratings.php?movie_id=$testMovieId",
    'GET'
);

// Test 5: CORS Preflight
echo "5. Testing CORS Support\n";
testEndpoint(
    'OPTIONS Request',
    $baseUrl . '/favorites.php',
    'OPTIONS'
);

echo "=====================================\n";
echo "✅ Test Complete!\n";
echo "=====================================\n\n";

echo "📋 Next Steps:\n";
echo "1. Open http://localhost/moviesuggestor/api/ in browser for full documentation\n";
echo "2. Use the examples in README.md for integration\n";
echo "3. Check ../logs/ for any error messages\n";
echo "4. Test with actual data using provided curl examples\n\n";
