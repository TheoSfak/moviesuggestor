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
    echo "Testing password verification...\n";
    $testPassword = "demo123";
    $isValid = Security::verifyPassword($testPassword, $user['password_hash']);
    echo "Password 'demo123': " . ($isValid ? "VALID" : "INVALID") . "\n";
    
    $altPassword = "Demo123!";
    $isValidAlt = Security::verifyPassword($altPassword, $user['password_hash']);
    echo "Password 'Demo123!': " . ($isValidAlt ? "VALID" : "INVALID") . "\n";
} else {
    echo "User not found\n";
}
