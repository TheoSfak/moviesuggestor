<?php

/**
 * Security Helper Functions
 * 
 * Centralized security functions for CSRF protection, session management,
 * and authentication. Include this file in all pages requiring security.
 * 
 * @package MovieSuggestor
 */

namespace MovieSuggestor;

class Security
{
    /**
     * Initialize secure session settings
     * Call this at the start of every page
     */
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure secure session settings
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1'); // Requires HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            
            // Set session timeout (30 minutes)
            ini_set('session.gc_maxlifetime', '1800');
            
            session_start();
            
            // Session timeout check
            if (isset($_SESSION['last_activity'])) {
                $inactive = time() - $_SESSION['last_activity'];
                if ($inactive > 1800) { // 30 minutes
                    self::destroySession();
                    return;
                }
            }
            $_SESSION['last_activity'] = time();
            
            // Regenerate session ID periodically (every 30 minutes)
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool True if valid
     */
    public static function validateCSRFToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token from request
     * Checks POST, GET, and header
     * 
     * @return string|null Token or null if not found
     */
    public static function getCSRFTokenFromRequest(): ?string
    {
        // Check POST
        if (isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }
        
        // Check custom header (for AJAX)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        // Check authorization header
        $headers = getallheaders();
        if (isset($headers['X-CSRF-Token'])) {
            return $headers['X-CSRF-Token'];
        }
        
        return null;
    }
    
    /**
     * Require CSRF token validation
     * Call this in all state-changing endpoints (POST, PUT, DELETE)
     * 
     * @throws \RuntimeException If token is invalid
     */
    public static function requireCSRFToken(): void
    {
        $token = self::getCSRFTokenFromRequest();
        
        if (!$token || !self::validateCSRFToken($token)) {
            http_response_code(403);
            if (self::isAjaxRequest()) {
                header('Content-Type: application/json');
                die(json_encode([
                    'success' => false,
                    'error' => 'Invalid or missing CSRF token'
                ]));
            } else {
                die('Invalid or missing CSRF token. Please refresh the page and try again.');
            }
        }
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if authenticated
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['authenticated']) && 
               $_SESSION['authenticated'] === true;
    }
    
    /**
     * Require authentication
     * Redirects to login if not authenticated
     * 
     * @param string $redirectUrl URL to redirect to after login
     */
    public static function requireAuth(string $redirectUrl = '/'): void
    {
        if (!self::isAuthenticated()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            if (self::isAjaxRequest()) {
                http_response_code(401);
                header('Content-Type: application/json');
                die(json_encode([
                    'success' => false,
                    'error' => 'Authentication required',
                    'redirect' => '/moviesuggestor/auth/login-page.php'
                ]));
            } else {
                header('Location: /moviesuggestor/auth/login-page.php');
                exit;
            }
        }
    }
    
    /**
     * Get current user ID from session
     * NEVER trust client-supplied user IDs
     * 
     * @return int|null User ID or null if not authenticated
     */
    public static function getUserId(): ?int
    {
        if (self::isAuthenticated()) {
            return (int)$_SESSION['user_id'];
        }
        return null;
    }
    
    /**
     * Destroy session and logout user
     */
    public static function destroySession(): void
    {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(
                session_name(),
                '',
                time() - 3600,
                '/',
                '',
                true,
                true
            );
        }
        
        session_destroy();
    }
    
    /**
     * Login user (after authentication)
     * 
     * @param int $userId User ID
     * @param array $userData Additional user data
     */
    public static function login(int $userId, array $userData = []): void
    {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $userData['username'] ?? 'User';
        $_SESSION['email'] = $userData['email'] ?? '';
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();
        
        // Generate new CSRF token
        unset($_SESSION['csrf_token']);
        self::generateCSRFToken();
    }
    
    /**
     * Logout user
     * 
     * @param string $redirectUrl Where to redirect after logout
     */
    public static function logout(string $redirectUrl = '/login.php'): void
    {
        self::destroySession();
        header("Location: $redirectUrl");
        exit;
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool True if AJAX request
     */
    public static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Validate CORS origin
     * 
     * @param array $allowedOrigins List of allowed origins
     * @return bool True if origin is allowed
     */
    public static function validateCORS(array $allowedOrigins = []): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (empty($allowedOrigins)) {
            // Default: only same origin
            return false;
        }
        
        if (in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
            header('Access-Control-Max-Age: 3600');
            return true;
        }
        
        return false;
    }
    
    /**
     * Hash password securely
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    /**
     * Verify password
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password
     * @return bool True if password matches
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Sanitize input for output (XSS prevention)
     * 
     * @param string $input Raw input
     * @return string Sanitized output
     */
    public static function escape(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email to validate
     * @return bool True if valid
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @param int $minLength Minimum length
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validatePasswordStrength(string $password, int $minLength = 8): array
    {
        $errors = [];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least $minLength characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Generate secure random token
     * 
     * @param int $length Length in bytes
     * @return string Hex token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Rate limiting check (simple implementation)
     * For production, use Redis or proper rate limiting library
     * 
     * @param string $key Rate limit key (e.g., user_id, IP)
     * @param int $maxAttempts Max attempts
     * @param int $timeWindow Time window in seconds
     * @return bool True if rate limit not exceeded
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $timeWindow = 60): bool
    {
        $rateLimitKey = "rate_limit_$key";
        
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = [
                'attempts' => 0,
                'reset_time' => time() + $timeWindow
            ];
        }
        
        $rateLimit = $_SESSION[$rateLimitKey];
        
        // Reset if time window expired
        if (time() > $rateLimit['reset_time']) {
            $_SESSION[$rateLimitKey] = [
                'attempts' => 1,
                'reset_time' => time() + $timeWindow
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($rateLimit['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$rateLimitKey]['attempts']++;
        return true;
    }
}
