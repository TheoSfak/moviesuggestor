<?php
/**
 * Logout Handler
 * 
 * Destroys session and redirects to login page
 * 
 * @package MovieSuggestor
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Security.php';

use MovieSuggestor\Security;

// Initialize session
Security::initSession();

// Destroy session and logout
Security::logout('/moviesuggestor/auth/login-page.php?logout=1');
