<?php
require_once 'C:\xampp\htdocs\moviesuggestor\vendor\autoload.php';
require_once 'C:\xampp\htdocs\moviesuggestor\src\Security.php';
require_once 'C:\xampp\htdocs\moviesuggestor\src\Database.php';

use MovieSuggestor\Security;
use MovieSuggestor\Database;

$db = new Database();
$pdo = $db->connect();

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE email = 'demo@example.com'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Stored hash: " . substr($user['password_hash'], 0, 50) . "...\n";
    echo "Hash algorithm: " . (strpos($user['password_hash'], '$argon2') !== false ? 'Argon2' : 'Other') . "\n";
    
    // Test password verification
    $testPassword = "demo123";
    $isValid = Security::verifyPassword($testPassword, $user['password_hash']);
    echo "Password 'demo123' verification: " . ($isValid ? "✓ VALID" : "✗ INVALID") . "\n";
    
    // Try alternative password
    $altPassword = "Demo123!";
    $isValidAlt = Security::verifyPassword($altPassword, $user['password_hash']);
    echo "Password 'Demo123!' verification: " . ($isValidAlt ? "✓ VALID" : "✗ INVALID") . "\n";
} else {
    echo "User not found!\n";
}
