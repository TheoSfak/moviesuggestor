<?php
/**
 * Registration Page
 * 
 * User registration with password strength validation
 * and email verification
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
    header('Location: /moviesuggestor/index.php');
    exit;
}

$error = '';
$success = '';
$formData = [];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Store form data for repopulation
    $formData = ['email' => $email, 'username' => $username];
    
    // Validate CSRF token
    if (!Security::validateCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please refresh and try again.';
    }
    // Validate inputs
    elseif (empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    }
    // Validate email format
    elseif (!Security::isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    }
    // Validate username length
    elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    }
    // Check password match
    elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    }
    else {
        // Validate password strength
        $passwordValidation = Security::validatePasswordStrength($password);
        
        if (!$passwordValidation['valid']) {
            $error = 'Password does not meet requirements:<br>• ' . 
                     implode('<br>• ', $passwordValidation['errors']);
        }
        else {
            try {
                $database = new Database();
                $db = $database->connect();
                
                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->execute(['email' => $email]);
                
                if ($stmt->fetch()) {
                    $error = 'An account with this email already exists.';
                } else {
                    // Hash password
                    $passwordHash = Security::hashPassword($password);
                    
                    // Generate verification token (for email verification - not implemented yet)
                    $verificationToken = Security::generateToken();
                    
                    // Insert user
                    $insertStmt = $db->prepare("
                        INSERT INTO users 
                        (email, password_hash, username, verification_token, created_at, is_active, is_verified)
                        VALUES (:email, :password_hash, :username, :verification_token, NOW(), TRUE, FALSE)
                    ");
                    
                    $result = $insertStmt->execute([
                        'email' => $email,
                        'password_hash' => $passwordHash,
                        'username' => $username,
                        'verification_token' => $verificationToken
                    ]);
                    
                    if ($result) {
                        $userId = $db->lastInsertId();
                        
                        // TODO: Send verification email
                        // For now, auto-verify for development
                        $verifyStmt = $db->prepare("UPDATE users SET is_verified = TRUE WHERE id = :id");
                        $verifyStmt->execute(['id' => $userId]);
                        
                        // Auto-login after registration
                        Security::login($userId, [
                            'username' => $username,
                            'email' => $email
                        ]);
                        
                        header('Location: /moviesuggestor/index.php?registered=1');
                        exit;
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
                
            } catch (Exception $e) {
                error_log("Registration error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Movie Suggestor</title>
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
        
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
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
        input[type="text"],
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
        
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
        
        .password-requirements ul {
            margin: 10px 0 0 20px;
        }
        
        .password-requirements li {
            margin: 5px 0;
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
            line-height: 1.6;
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
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1>🎬 Movie Suggestor</h1>
            <p>Create your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= Security::escape($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    autofocus
                    value="<?= Security::escape($formData['email'] ?? '') ?>"
                    placeholder="you@example.com"
                >
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required
                    value="<?= Security::escape($formData['username'] ?? '') ?>"
                    placeholder="Choose a username"
                    minlength="3"
                    maxlength="50"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="Create a strong password"
                >
                <div class="password-requirements">
                    <strong>Password must contain:</strong>
                    <ul>
                        <li>At least 8 characters</li>
                        <li>One uppercase letter</li>
                        <li>One lowercase letter</li>
                        <li>One number</li>
                        <li>One special character</li>
                    </ul>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                    placeholder="Re-enter your password"
                >
            </div>
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <div class="links">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
            <p style="margin-top: 10px;"><a href="index.php">Continue as guest (limited access)</a></p>
        </div>
    </div>
    
    <script>
        // Real-time password confirmation check
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        confirmPassword.addEventListener('input', function() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
