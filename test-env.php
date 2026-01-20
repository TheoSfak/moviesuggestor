<?php

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $envFile = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envFile as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

echo "Testing .env loading:\n";
echo "getenv: " . getenv('TMDB_API_KEY') . "\n";
echo "_ENV: " . ($_ENV['TMDB_API_KEY'] ?? 'NOT SET') . "\n";
echo "_SERVER: " . ($_SERVER['TMDB_API_KEY'] ?? 'NOT SET') . "\n";
