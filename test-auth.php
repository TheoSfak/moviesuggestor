<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .link-grid {
            display: grid;
            gap: 15px;
        }
        
        .link-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            display: block;
        }
        
        .link-card:hover {
            border-color: #667eea;
            background: #f0f3ff;
            transform: translateY(-2px);
        }
        
        .link-card h3 {
            color: #667eea;
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .link-card p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        
        .status {
            text-align: center;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 8px;
            margin-bottom: 30px;
            color: #1976d2;
        }
        
        .icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🎬</div>
        <h1>Movie Suggestor - Authentication System</h1>
        
        <?php
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            echo '<div class="status">✅ You are logged in!</div>';
        } else {
            echo '<div class="status">ℹ️ You are not logged in</div>';
        }
        ?>
        
        <div class="link-grid">
            <a href="auth/login-page.php" class="link-card">
                <h3>🔐 Login Page</h3>
                <p>Sign in to your account with email and password</p>
            </a>
            
            <a href="auth/register-page.php" class="link-card">
                <h3>📝 Registration Page</h3>
                <p>Create a new account with real-time validation</p>
            </a>
            
            <a href="auth/profile.php" class="link-card">
                <h3>👤 User Profile</h3>
                <p>View your statistics and recent activity</p>
            </a>
            
            <a href="auth/forgot-password.php" class="link-card">
                <h3>🔑 Forgot Password</h3>
                <p>Reset your password via email</p>
            </a>
            
            <a href="index.php" class="link-card">
                <h3>🏠 Main Application</h3>
                <p>Go to the movie suggestor app (requires login)</p>
            </a>
            
            <a href="logout.php" class="link-card">
                <h3>🚪 Logout</h3>
                <p>End your session and return to login</p>
            </a>
        </div>
        
        <div style="text-align: center; margin-top: 30px; color: #999; font-size: 12px;">
            <strong>Demo Credentials:</strong><br>
            Email: demo@example.com | Password: demo123
        </div>
    </div>
</body>
</html>
