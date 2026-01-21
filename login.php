<?php
/**
 * Login Page
 * 
 * User authentication with secure password verification
 * Implements rate limiting and account lockout
 * 
 * @package MovieSuggestor
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Security.php';

use MovieSuggestor\Security;
use MovieSuggestor\Database;

// Initialize secure session
Security::initSession();

// Redirect if already authenticated
if (Security::isAuthenticated()) {
    $redirect = $_SESSION['redirect_after_login'] ?? '/moviesuggestor/index.php';
    unset($_SESSION['redirect_after_login']);
    header("Location: $redirect");
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!Security::validateCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please refresh and try again.';
    }
    // Validate inputs
    elseif (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    }
    // Check rate limiting
    elseif (!Security::checkRateLimit('login_' . $_SERVER['REMOTE_ADDR'], 5, 300)) {
        $error = 'Too many login attempts. Please try again in 5 minutes.';
    }
    else {
        try {
            $database = new Database();
            $db = $database->connect();
            
            // Get user by email
            $stmt = $db->prepare("
                SELECT id, email, password_hash, username, is_active, 
                       failed_login_attempts, locked_until
                FROM users 
                WHERE email = :email
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log login attempt
            $attemptStmt = $db->prepare("
                INSERT INTO login_attempts 
                (user_id, email, ip_address, user_agent, success, failure_reason, attempted_at)
                VALUES (:user_id, :email, :ip, :user_agent, :success, :reason, NOW())
            ");
            
            if (!$user) {
                // User not found
                $attemptStmt->execute([
                    'user_id' => null,
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'success' => 0,
                    'reason' => 'User not found'
                ]);
                
                $error = 'Invalid email or password.';
            }
            // Check if account is locked
            elseif ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $minutesLeft = ceil((strtotime($user['locked_until']) - time()) / 60);
                $error = "Account is locked due to multiple failed login attempts. Try again in $minutesLeft minutes.";
                
                $attemptStmt->execute([
                    'user_id' => $user['id'],
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'success' => 0,
                    'reason' => 'Account locked'
                ]);
            }
            // Check if account is active
            elseif (!$user['is_active']) {
                $error = 'This account has been deactivated. Please contact support.';
                
                $attemptStmt->execute([
                    'user_id' => $user['id'],
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'success' => 0,
                    'reason' => 'Account inactive'
                ]);
            }
            // Verify password
            elseif (!Security::verifyPassword($password, $user['password_hash'])) {
                // Increment failed attempts
                $failedAttempts = (int)$user['failed_login_attempts'] + 1;
                
                // Lock account after 5 failed attempts
                if ($failedAttempts >= 5) {
                    $lockUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    $updateStmt = $db->prepare("
                        UPDATE users 
                        SET failed_login_attempts = :attempts, 
                            locked_until = :lock_until 
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        'attempts' => $failedAttempts,
                        'lock_until' => $lockUntil,
                        'id' => $user['id']
                    ]);
                    
                    $error = 'Too many failed attempts. Account locked for 30 minutes.';
                } else {
                    $updateStmt = $db->prepare("
                        UPDATE users 
                        SET failed_login_attempts = :attempts 
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        'attempts' => $failedAttempts,
                        'id' => $user['id']
                    ]);
                    
                    $remainingAttempts = 5 - $failedAttempts;
                    $error = "Invalid email or password. $remainingAttempts attempts remaining.";
                }
                
                $attemptStmt->execute([
                    'user_id' => $user['id'],
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'success' => 0,
                    'reason' => 'Invalid password'
                ]);
            }
            // Success - login user
            else {
                // Reset failed attempts and update last login
                $updateStmt = $db->prepare("
                    UPDATE users 
                    SET failed_login_attempts = 0, 
                        locked_until = NULL, 
                        last_login = NOW() 
                    WHERE id = :id
                ");
                $updateStmt->execute(['id' => $user['id']]);
                
                // Log successful login
                $attemptStmt->execute([
                    'user_id' => $user['id'],
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'success' => 1,
                    'reason' => null
                ]);
                
                // Set session variables
                Security::login($user['id'], [
                    'username' => $user['username'],
                    'email' => $user['email']
                ]);
                
                // Redirect to original destination or homepage
                $redirect = $_SESSION['redirect_after_login'] ?? '/moviesuggestor/index.php';
                unset($_SESSION['redirect_after_login']);
                
                header("Location: $redirect");
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Movie Suggestor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            font-size: 14px;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🎬 Movie Suggestor</h1>
            <p>Sign in to your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= Security::escape($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= Security::escape($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    autofocus
                    value="<?= isset($_POST['email']) ? Security::escape($_POST['email']) : '' ?>"
                    placeholder="you@example.com"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="Enter your password"
                >
            </div>
            
            <button type="submit" class="btn">Sign In</button>
        </form>
        
        <div class="divider">───────  or  ───────</div>
        
        <div class="links">
            <p>Don't have an account? <a href="register.php">Create one</a></p>
            <p style="margin-top: 10px;"><a href="index.php">Continue as guest (limited access)</a></p>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
            <small style="color: #999;">
                Demo accounts:<br>
                📧 demo@example.com | 🔑 demo123
            </small>
        </div>
    </div>
</body>
</html>
