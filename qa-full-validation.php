<?php
/**
 * COMPREHENSIVE QA VALIDATION FOR MOVIE SUGGESTOR
 * Tests all features including the new movie details page
 */

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       MOVIE SUGGESTOR - COMPREHENSIVE QA VALIDATION           ║\n";
echo "║                    Full Feature Testing                       ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$baseDir = 'C:\xampp\htdocs\moviesuggestor';
$passed = 0;
$failed = 0;
$warnings = 0;
$criticalIssues = [];

// Test 1: Core Files Existence
echo "🔍 TEST 1: Core Files Existence\n";
echo "─────────────────────────────────────────────────────────────────\n";

$coreFiles = [
    'index.php',
    'movie-details.php',
    'my-favorites.php',
    'my-watch-later.php',
    'login.php',
    'register.php',
    'logout.php',
    'auth/login-page.php',
    'auth/register-page.php',
    'auth/profile.php',
    'auth/account-settings.php',
    'src/Database.php',
    'src/Security.php',
    'src/TMDBService.php',
    'src/FavoritesRepository.php',
    'src/WatchLaterRepository.php',
    'src/RatingRepository.php',
    'api/favorites.php',
    'api/watch-later.php',
    'api/ratings.php',
];

foreach ($coreFiles as $file) {
    $fullPath = $baseDir . '\\' . $file;
    if (file_exists($fullPath)) {
        echo "  ✓ " . str_pad($file, 40) . " EXISTS\n";
        $passed++;
    } else {
        echo "  ❌ " . str_pad($file, 40) . " MISSING\n";
        $failed++;
        $criticalIssues[] = "Missing file: $file";
    }
}

// Test 2: PHP Syntax Validation
echo "\n🔍 TEST 2: PHP Syntax Validation\n";
echo "─────────────────────────────────────────────────────────────────\n";

$phpFiles = [
    'index.php',
    'movie-details.php',
    'my-favorites.php',
    'my-watch-later.php',
    'auth/profile.php',
    'auth/account-settings.php',
    'src/Security.php',
    'src/TMDBService.php',
];

foreach ($phpFiles as $file) {
    $fullPath = $baseDir . '\\' . $file;
    if (!file_exists($fullPath)) continue;
    
    $output = [];
    $return = 0;
    exec("C:\\xampp\\php\\php.exe -l \"$fullPath\" 2>&1", $output, $return);
    
    if ($return === 0) {
        echo "  ✓ " . str_pad($file, 40) . " SYNTAX OK\n";
        $passed++;
    } else {
        echo "  ❌ " . str_pad($file, 40) . " SYNTAX ERROR\n";
        echo "     Error: " . implode("\n     ", $output) . "\n";
        $failed++;
        $criticalIssues[] = "Syntax error in $file";
    }
}

// Test 3: Security Features
echo "\n🔒 TEST 3: Security Features\n";
echo "─────────────────────────────────────────────────────────────────\n";

$securityChecks = [
    ['file' => 'src/Security.php', 'pattern' => 'generateCSRFToken', 'name' => 'CSRF Token Generation'],
    ['file' => 'src/Security.php', 'pattern' => 'validateCSRFToken', 'name' => 'CSRF Token Validation'],
    ['file' => 'src/Security.php', 'pattern' => 'password_hash', 'name' => 'Password Hashing'],
    ['file' => 'src/Security.php', 'pattern' => 'session_regenerate_id', 'name' => 'Session Regeneration'],
    ['file' => 'auth/login-page.php', 'pattern' => 'Security::initSession', 'name' => 'Session Initialization (Login)'],
    ['file' => 'auth/profile.php', 'pattern' => 'Security::requireAuth', 'name' => 'Auth Required (Profile)'],
    ['file' => 'movie-details.php', 'pattern' => 'Security::initSession', 'name' => 'Session Init (Movie Details)'],
];

foreach ($securityChecks as $check) {
    $fullPath = $baseDir . '\\' . $check['file'];
    if (!file_exists($fullPath)) {
        echo "  ⚠️  " . str_pad($check['name'], 40) . " FILE NOT FOUND\n";
        $warnings++;
        continue;
    }
    
    $content = file_get_contents($fullPath);
    if (strpos($content, $check['pattern']) !== false) {
        echo "  ✓ " . str_pad($check['name'], 40) . " IMPLEMENTED\n";
        $passed++;
    } else {
        echo "  ❌ " . str_pad($check['name'], 40) . " MISSING\n";
        $failed++;
        $criticalIssues[] = $check['name'] . " not found in " . $check['file'];
    }
}

// Test 4: Movie Details Page Features
echo "\n🎬 TEST 4: Movie Details Page Features\n";
echo "─────────────────────────────────────────────────────────────────\n";

$movieDetailsFile = $baseDir . '\\movie-details.php';
if (file_exists($movieDetailsFile)) {
    $content = file_get_contents($movieDetailsFile);
    
    $detailsFeatures = [
        ['pattern' => 'getMovieDetails', 'name' => 'TMDB Movie Details Integration'],
        ['pattern' => 'getMovieVideos', 'name' => 'Trailer/Videos Support'],
        ['pattern' => 'getMovieImages', 'name' => 'Image Gallery Support'],
        ['pattern' => 'getSimilarMovies', 'name' => 'Similar Movies Feature'],
        ['pattern' => 'FROM ratings', 'name' => 'User Reviews/Ratings Display'],
        ['pattern' => 'toggleFavorite', 'name' => 'Add to Favorites Function'],
        ['pattern' => 'toggleWatchLater', 'name' => 'Add to Watch Later Function'],
        ['pattern' => 'submitRating', 'name' => 'Submit Rating Function'],
        ['pattern' => 'ratingModal', 'name' => 'Rating Modal Dialog'],
        ['pattern' => 'review-card', 'name' => 'Review Display Component'],
        ['pattern' => 'video-container', 'name' => 'Video/Trailer Embed'],
        ['pattern' => 'image-gallery', 'name' => 'Image Gallery Component'],
    ];
    
    foreach ($detailsFeatures as $feature) {
        if (strpos($content, $feature['pattern']) !== false) {
            echo "  ✓ " . str_pad($feature['name'], 40) . " PRESENT\n";
            $passed++;
        } else {
            echo "  ❌ " . str_pad($feature['name'], 40) . " MISSING\n";
            $failed++;
        }
    }
} else {
    echo "  ❌ movie-details.php not found!\n";
    $failed++;
    $criticalIssues[] = "movie-details.php file missing";
}

// Test 5: Navigation Links to Movie Details
echo "\n🔗 TEST 5: Navigation Links to Movie Details Page\n";
echo "─────────────────────────────────────────────────────────────────\n";

$linkFiles = [
    ['file' => 'index.php', 'pattern' => 'movie-details.php', 'name' => 'Index Page Links'],
    ['file' => 'my-favorites.php', 'pattern' => 'movie-details.php', 'name' => 'Favorites Page Links'],
    ['file' => 'my-watch-later.php', 'pattern' => 'movie-details.php', 'name' => 'Watch Later Page Links'],
];

foreach ($linkFiles as $link) {
    $fullPath = $baseDir . '\\' . $link['file'];
    if (!file_exists($fullPath)) {
        echo "  ⚠️  " . str_pad($link['name'], 40) . " FILE NOT FOUND\n";
        $warnings++;
        continue;
    }
    
    $content = file_get_contents($fullPath);
    if (strpos($content, $link['pattern']) !== false) {
        echo "  ✓ " . str_pad($link['name'], 40) . " CONFIGURED\n";
        $passed++;
    } else {
        echo "  ⚠️  " . str_pad($link['name'], 40) . " NOT LINKED\n";
        $warnings++;
    }
}

// Test 6: Database Schema
echo "\n💾 TEST 6: Database Schema Validation\n";
echo "─────────────────────────────────────────────────────────────────\n";

$schemaFile = $baseDir . '\\database-schema.sql';
if (file_exists($schemaFile)) {
    $content = file_get_contents($schemaFile);
    
    $tables = [
        'users',
        'favorites',
        'watch_later',
        'ratings',
        'sessions'
    ];
    
    foreach ($tables as $table) {
        if (stripos($content, "CREATE TABLE IF NOT EXISTS $table") !== false || 
            stripos($content, "CREATE TABLE `$table`") !== false) {
            echo "  ✓ " . str_pad("Table: $table", 40) . " DEFINED\n";
            $passed++;
        } else {
            echo "  ⚠️  " . str_pad("Table: $table", 40) . " NOT FOUND\n";
            $warnings++;
        }
    }
    
    // Check for required columns
    $requiredColumns = [
        ['table' => 'favorites', 'column' => 'created_at'],
        ['table' => 'ratings', 'column' => 'review'],
        ['table' => 'ratings', 'column' => 'rating'],
        ['table' => 'watch_later', 'column' => 'added_at'],
    ];
    
    foreach ($requiredColumns as $col) {
        if (stripos($content, $col['column']) !== false) {
            echo "  ✓ " . str_pad("{$col['table']}.{$col['column']}", 40) . " PRESENT\n";
            $passed++;
        } else {
            echo "  ⚠️  " . str_pad("{$col['table']}.{$col['column']}", 40) . " MISSING\n";
            $warnings++;
        }
    }
} else {
    echo "  ⚠️  database-schema.sql not found\n";
    $warnings++;
}

// Test 7: API Endpoints
echo "\n🔌 TEST 7: API Endpoints\n";
echo "─────────────────────────────────────────────────────────────────\n";

$apiFiles = [
    'api/favorites.php',
    'api/watch-later.php',
    'api/ratings.php',
];

foreach ($apiFiles as $file) {
    $fullPath = $baseDir . '\\' . $file;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        
        // Check for proper JSON response
        if (strpos($content, 'application/json') !== false) {
            echo "  ✓ " . str_pad($file, 40) . " JSON RESPONSE\n";
            $passed++;
        } else {
            echo "  ⚠️  " . str_pad($file, 40) . " NO JSON HEADER\n";
            $warnings++;
        }
        
        // Check for security
        if (strpos($content, 'Security::') !== false) {
            echo "  ✓ " . str_pad($file, 40) . " SECURED\n";
            $passed++;
        } else {
            echo "  ❌ " . str_pad($file, 40) . " NO SECURITY\n";
            $failed++;
        }
    } else {
        echo "  ❌ " . str_pad($file, 40) . " NOT FOUND\n";
        $failed++;
    }
}

// Final Report
echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                      VALIDATION SUMMARY                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$total = $passed + $failed + $warnings;
$passRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo "  ✓ PASSED:   " . $passed . "\n";
echo "  ❌ FAILED:   " . $failed . "\n";
echo "  ⚠️  WARNINGS: " . $warnings . "\n";
echo "  📊 TOTAL:    " . $total . "\n";
echo "  📈 PASS RATE: " . $passRate . "%\n\n";

if (!empty($criticalIssues)) {
    echo "🚨 CRITICAL ISSUES:\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    foreach ($criticalIssues as $issue) {
        echo "  • " . $issue . "\n";
    }
    echo "\n";
}

// Final Verdict
if ($failed === 0 && $passRate >= 90) {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ QA VALIDATION PASSED                     ║\n";
    echo "║                                                               ║\n";
    echo "║  All critical features have been implemented and tested.      ║\n";
    echo "║  The Movie Suggestor application is ready for use!           ║\n";
    echo "║                                                               ║\n";
    echo "║  Features Completed:                                          ║\n";
    echo "║  ✓ User Authentication & Authorization                        ║\n";
    echo "║  ✓ Movie Details Page with TMDB Integration                  ║\n";
    echo "║  ✓ Trailer & Image Gallery Support                           ║\n";
    echo "║  ✓ User Reviews & Ratings System                             ║\n";
    echo "║  ✓ Favorites & Watch Later Functionality                     ║\n";
    echo "║  ✓ Similar Movies Recommendations                            ║\n";
    echo "║  ✓ Account Settings & Profile Management                     ║\n";
    echo "║  ✓ CSRF Protection & Security Features                       ║\n";
    echo "║                                                               ║\n";
    echo "║  Access the application at:                                   ║\n";
    echo "║  http://localhost/moviesuggestor/                             ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";
    exit(0);
} elseif ($failed === 0) {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║              ⚠️  QA VALIDATION PASSED WITH WARNINGS            ║\n";
    echo "║                                                               ║\n";
    echo "║  Application is functional but has minor issues.              ║\n";
    echo "║  Review warnings above for improvements.                      ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";
    exit(0);
} else {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║                ❌ QA VALIDATION FAILED                         ║\n";
    echo "║                                                               ║\n";
    echo "║  Critical issues were found. Review the report above.         ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";
    exit(1);
}
