<?php
/**
 * System Verification Script
 * Tests all components of Movie Suggestor
 */

echo "==============================================\n";
echo "Movie Suggestor - System Verification\n";
echo "==============================================\n\n";

// Test 1: PHP Version
echo "1. PHP Version Check...\n";
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.0', '>=')) {
    echo "   ✓ PHP $phpVersion (OK)\n";
} else {
    echo "   ✗ PHP $phpVersion (Need 8.0+)\n";
}
echo "\n";

// Test 2: Required Extensions
echo "2. PHP Extensions Check...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'curl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ $ext enabled\n";
    } else {
        echo "   ✗ $ext missing\n";
    }
}
echo "\n";

// Test 3: .env File
echo "3. Environment Configuration...\n";
if (file_exists(__DIR__ . '/.env')) {
    echo "   ✓ .env file exists\n";
    
    // Load .env
    $envFile = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $envVars = [];
    foreach ($envFile as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $envVars[$key] = $value;
        }
    }
    
    // Check required variables
    $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'TMDB_API_KEY'];
    foreach ($required as $var) {
        if (!empty(getenv($var))) {
            $display = ($var === 'TMDB_API_KEY') ? substr(getenv($var), 0, 10) . '...' : getenv($var);
            echo "   ✓ $var = $display\n";
        } else {
            echo "   ✗ $var not set\n";
        }
    }
} else {
    echo "   ✗ .env file not found\n";
    echo "   → Copy .env.example to .env and configure\n";
}
echo "\n";

// Test 4: Database Connection
echo "4. Database Connection...\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/src/Database.php';
    
    $db = new MovieSuggestor\Database();
    $conn = $db->connect();
    echo "   ✓ Database connected successfully\n";
    
    // Check tables
    $tables = ['movies', 'favorites', 'watch_later', 'ratings'];
    $stmt = $conn->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        if (in_array($table, $existingTables)) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ✗ Table '$table' missing\n";
        }
    }
    
    // Check TMDB columns
    if (in_array('favorites', $existingTables)) {
        $stmt = $conn->query("DESCRIBE favorites");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('tmdb_id', $columns)) {
            echo "   ✓ TMDB integration columns exist\n";
        } else {
            echo "   ✗ TMDB columns missing (run migration 007)\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: TMDB API
echo "5. TMDB API Connection...\n";
try {
    require_once __DIR__ . '/src/TMDBService.php';
    
    $tmdbService = new MovieSuggestor\TMDBService();
    $result = $tmdbService->searchMovies('Inception', 1);
    
    if (isset($result['success']) && $result['success']) {
        echo "   ✓ TMDB API working\n";
        echo "   ✓ Found " . count($result['results']) . " movies\n";
        
        // Check for description
        if (!empty($result['results'][0]['description'])) {
            echo "   ✓ Movie descriptions available\n";
        } else {
            echo "   ✗ Movie descriptions empty\n";
        }
        
        // Check for IMDB rating
        if (isset($result['results'][0]['imdb_rating'])) {
            echo "   ✓ IMDB ratings included\n";
        } else {
            echo "   ⚠ IMDB ratings not available\n";
        }
    } else {
        echo "   ✗ TMDB API error: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ TMDB API test failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Repository Classes
echo "6. Repository Classes...\n";
try {
    require_once __DIR__ . '/src/FavoritesRepository.php';
    require_once __DIR__ . '/src/RatingRepository.php';
    
    $favRepo = new MovieSuggestor\FavoritesRepository($db);
    $ratingRepo = new MovieSuggestor\RatingRepository($db);
    
    echo "   ✓ FavoritesRepository loaded\n";
    echo "   ✓ RatingRepository loaded\n";
    
    // Test favorite operations (dry run)
    $testUserId = 999999;
    $testTmdbId = 27205; // Inception
    
    // Test if methods exist and accept correct parameters
    if (method_exists($favRepo, 'isFavorite')) {
        echo "   ✓ FavoritesRepository->isFavorite() exists\n";
    }
    
    if (method_exists($ratingRepo, 'getUserRating')) {
        echo "   ✓ RatingRepository->getUserRating() exists\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Repository test failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: API Endpoints
echo "7. API Endpoints...\n";
$apiFiles = ['favorites.php', 'ratings.php', 'watch-later.php'];
foreach ($apiFiles as $file) {
    $path = __DIR__ . '/api/' . $file;
    if (file_exists($path)) {
        echo "   ✓ api/$file exists\n";
    } else {
        echo "   ✗ api/$file missing\n";
    }
}
echo "\n";

// Test 8: File Permissions (Unix-like systems)
if (PHP_OS_FAMILY !== 'Windows') {
    echo "8. File Permissions...\n";
    $checkDirs = ['.', 'api', 'src'];
    foreach ($checkDirs as $dir) {
        if (is_readable($dir)) {
            echo "   ✓ $dir is readable\n";
        } else {
            echo "   ✗ $dir is not readable\n";
        }
    }
    echo "\n";
}

// Summary
echo "==============================================\n";
echo "Verification Complete!\n";
echo "==============================================\n\n";

echo "Next Steps:\n";
echo "1. Fix any errors shown above\n";
echo "2. Access the application in your browser\n";
echo "3. Test search, filters, favorites, and ratings\n";
echo "4. Check browser console for JavaScript errors\n\n";

echo "If all tests passed:\n";
echo "✓ Your Movie Suggestor installation is ready!\n";
echo "✓ Access at: http://localhost/moviesuggestor/\n\n";
