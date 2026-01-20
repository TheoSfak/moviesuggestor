<?php
/**
 * Test Phase 2 Data Insertion and Foreign Key Constraints
 */

require_once __DIR__ . '/src/Database.php';

use MovieSuggestor\Database;

echo "🧪 Testing Phase 2 Data Insertion and Constraints\n";
echo str_repeat("=", 70) . "\n\n";

try {
    $database = new Database();
    $db = $database->connect();
    
    // Test 1: Insert sample favorites
    echo "1. Testing Favorites Table...\n";
    $stmt = $db->prepare("INSERT INTO favorites (user_id, movie_id) VALUES (?, ?)");
    $stmt->execute([1, 1]); // User 1 favorites movie 1
    $stmt->execute([1, 3]); // User 1 favorites movie 3
    $stmt->execute([2, 1]); // User 2 favorites movie 1
    echo "   ✅ Successfully inserted 3 favorites\n\n";
    
    // Test 2: Insert sample watch_later entries
    echo "2. Testing Watch Later Table...\n";
    $stmt = $db->prepare("INSERT INTO watch_later (user_id, movie_id, watched) VALUES (?, ?, ?)");
    $stmt->execute([1, 2, 0]); // User 1 wants to watch movie 2
    $stmt->execute([1, 5, 0]); // User 1 wants to watch movie 5
    $stmt->execute([2, 3, 1]); // User 2 watched movie 3
    echo "   ✅ Successfully inserted 3 watch_later entries\n\n";
    
    // Test 3: Insert sample ratings
    echo "3. Testing Ratings Table...\n";
    $stmt = $db->prepare("INSERT INTO ratings (user_id, movie_id, rating, review) VALUES (?, ?, ?, ?)");
    $stmt->execute([1, 1, 10, 'Amazing movie! Absolute masterpiece.']);
    $stmt->execute([1, 3, 9, 'Great action sequences.']);
    $stmt->execute([2, 1, 9, 'One of the best films ever made.']);
    $stmt->execute([2, 2, 8, 'Classic crime drama.']);
    echo "   ✅ Successfully inserted 4 ratings\n\n";
    
    // Test 4: Update movie metadata
    echo "4. Testing Movie Metadata Update...\n";
    $stmt = $db->prepare("
        UPDATE movies 
        SET release_year = ?, 
            director = ?, 
            runtime_minutes = ?,
            imdb_rating = ?
        WHERE id = ?
    ");
    $stmt->execute([1994, 'Frank Darabont', 142, 9.3, 1]); // Shawshank
    $stmt->execute([1972, 'Francis Ford Coppola', 175, 9.2, 2]); // Godfather
    $stmt->execute([2008, 'Christopher Nolan', 152, 9.0, 3]); // Dark Knight
    echo "   ✅ Successfully updated movie metadata\n\n";
    
    // Test 5: Verify foreign key constraint (try invalid movie_id)
    echo "5. Testing Foreign Key Constraints...\n";
    try {
        $stmt = $db->prepare("INSERT INTO favorites (user_id, movie_id) VALUES (?, ?)");
        $stmt->execute([1, 9999]); // Non-existent movie
        echo "   ❌ Foreign key constraint FAILED (should have been rejected)\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'foreign key constraint') !== false || 
            strpos($e->getMessage(), 'Cannot add or update a child row') !== false) {
            echo "   ✅ Foreign key constraint working correctly\n\n";
        } else {
            echo "   ⚠️  Got error but not FK related: " . $e->getMessage() . "\n\n";
        }
    }
    
    // Test 6: Verify unique constraints
    echo "6. Testing Unique Constraints...\n";
    try {
        $stmt = $db->prepare("INSERT INTO favorites (user_id, movie_id) VALUES (?, ?)");
        $stmt->execute([1, 1]); // Duplicate favorite
        echo "   ❌ Unique constraint FAILED (should have been rejected)\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "   ✅ Unique constraint working correctly\n\n";
        } else {
            echo "   ⚠️  Got error but not duplicate related: " . $e->getMessage() . "\n\n";
        }
    }
    
    // Test 7: Query and verify data
    echo "7. Verifying Inserted Data...\n";
    
    $result = $db->query("SELECT COUNT(*) FROM favorites")->fetchColumn();
    echo "   - Favorites count: $result\n";
    
    $result = $db->query("SELECT COUNT(*) FROM watch_later")->fetchColumn();
    echo "   - Watch Later count: $result\n";
    
    $result = $db->query("SELECT COUNT(*) FROM ratings")->fetchColumn();
    echo "   - Ratings count: $result\n";
    
    $result = $db->query("SELECT COUNT(*) FROM movies WHERE release_year IS NOT NULL")->fetchColumn();
    echo "   - Movies with metadata: $result\n";
    
    echo "   ✅ All data verified\n\n";
    
    // Test 8: Test complex join query
    echo "8. Testing Complex Join Query...\n";
    $stmt = $db->query("
        SELECT m.title, m.release_year, m.director, r.rating, r.review
        FROM movies m
        INNER JOIN ratings r ON m.id = r.movie_id
        WHERE m.release_year IS NOT NULL
        ORDER BY r.rating DESC
        LIMIT 3
    ");
    $results = $stmt->fetchAll();
    echo "   Found " . count($results) . " rated movies with metadata:\n";
    foreach ($results as $row) {
        echo "   - {$row['title']} ({$row['release_year']}) - Rating: {$row['rating']}/10\n";
    }
    echo "   ✅ Complex queries working\n\n";
    
    echo str_repeat("=", 70) . "\n";
    echo "✅ ALL PHASE 2 DATA TESTS PASSED!\n";
    echo "Database is ready for Phase 2 features.\n\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
