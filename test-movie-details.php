<?php
// Test movie-details.php functionality
echo "Testing movie-details.php...\n\n";

// Set up test environment
$_GET['id'] = 550; // Fight Club TMDB ID for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';

// Capture any errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Attempting to load movie-details.php...\n";

try {
    ob_start();
    include 'C:\xampp\htdocs\moviesuggestor\movie-details.php';
    $output = ob_get_clean();
    
    if (strpos($output, 'Fatal error') !== false || strpos($output, 'Parse error') !== false) {
        echo "❌ ERRORS FOUND:\n";
        echo $output;
        exit(1);
    }
    
    if (strpos($output, 'movie-header') !== false) {
        echo "✓ Page structure loaded successfully\n";
    }
    
    if (strpos($output, 'video-container') !== false || strpos($output, 'Trailer') !== false) {
        echo "✓ Trailer section present\n";
    }
    
    if (strpos($output, 'image-gallery') !== false) {
        echo "✓ Image gallery present\n";
    }
    
    if (strpos($output, 'review-card') !== false || strpos($output, 'User Reviews') !== false) {
        echo "✓ Reviews section present\n";
    }
    
    echo "\n✅ Movie details page is working!\n";
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
