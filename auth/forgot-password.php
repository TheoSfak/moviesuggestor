<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Movie Suggestor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .forgot-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .forgot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .forgot-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .forgot-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .forgot-body {
            padding: 40px 30px;
        }
        
        .info-message {
            background: #e3f2fd;
            color: #1976d2;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            border-left: 4px solid #1976d2;
        }
        
        .success-message {
            background: #efe;
            color: #3c3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            border-left: 4px solid #3c3;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .form-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .icon-large {
            font-size: 64px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1>🔐 Reset Password</h1>
            <p>We'll help you get back into your account</p>
        </div>
        
        <div class="forgot-body">
            <?php
            session_start();
            
            $submitted = false;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $submitted = true;
                // In a real implementation, this would:
                // 1. Verify email exists
                // 2. Generate reset token
                // 3. Send reset email
                // 4. Store token with expiration
            }
            ?>
            
            <?php if (!$submitted): ?>
                <div class="info-message">
                    Enter your email address and we'll send you a link to reset your password.
                </div>
                
                <form method="POST" id="forgotForm">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            placeholder="your.email@example.com"
                        >
                    </div>
                    
                    <button type="submit" class="btn" id="submitBtn">
                        Send Reset Link
                    </button>
                </form>
            <?php else: ?>
                <div class="icon-large">📧</div>
                <div class="success-message">
                    <strong>Check your email!</strong><br><br>
                    If an account exists with that email, we've sent password reset instructions to:<br>
                    <strong><?= htmlspecialchars($_POST['email'] ?? '') ?></strong>
                    <br><br>
                    The link will expire in 1 hour.
                </div>
            <?php endif; ?>
            
            <div class="form-footer">
                <a href="login-page.php">← Back to Login</a>
            </div>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('forgotForm');
        if (form) {
            const submitBtn = document.getElementById('submitBtn');
            
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading"></span>Sending...';
            });
        }
    </script>
</body>
</html>
