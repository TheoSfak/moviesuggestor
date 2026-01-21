<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Security.php';

use MovieSuggestor\Security;

// Initialize session BEFORE any output
Security::initSession();
$csrfToken = Security::generateCSRFToken();

// Check for messages
$showError = isset($_SESSION['error']) ? $_SESSION['error'] : null;
if ($showError) unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Movie Suggestor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .register-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .register-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .register-body {
            padding: 40px 30px;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
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
            border-color: #f5576c;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
        }
        
        .form-group input.error {
            border-color: #e74c3c;
        }
        
        .form-group input.success {
            border-color: #2ecc71;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
        }
        
        .field-error {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
        .field-success {
            color: #2ecc71;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-meter {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .strength-meter-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            background: #e74c3c;
        }
        
        .strength-meter-fill.weak {
            width: 33%;
            background: #e74c3c;
        }
        
        .strength-meter-fill.medium {
            width: 66%;
            background: #f39c12;
        }
        
        .strength-meter-fill.strong {
            width: 100%;
            background: #2ecc71;
        }
        
        .strength-text {
            font-size: 12px;
            color: #666;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .password-requirements ul {
            list-style: none;
            margin-top: 5px;
        }
        
        .password-requirements li {
            padding: 3px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .password-requirements li::before {
            content: '○';
            position: absolute;
            left: 0;
            color: #999;
        }
        
        .password-requirements li.valid::before {
            content: '✓';
            color: #2ecc71;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 87, 108, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .form-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .form-footer a {
            color: #f5576c;
            text-decoration: none;
            font-weight: 600;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .terms {
            font-size: 12px;
            color: #999;
            margin-top: 15px;
            text-align: center;
        }
        
        .terms a {
            color: #f5576c;
            text-decoration: none;
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
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>🎬 Create Account</h1>
            <p>Join Movie Suggestor and start your journey</p>
        </div>
        
        <div class="register-body">
            <?php
            if ($showError) {
                echo '<div class="error-message">' . htmlspecialchars($showError) . '</div>';
            }
            ?>
            
            <form action="../register.php" method="POST" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        placeholder="Choose a username"
                        minlength="3"
                        maxlength="50"
                    >
                    <div class="field-error" id="usernameError"></div>
                    <div class="field-success" id="usernameSuccess">✓ Username looks good</div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        placeholder="your.email@example.com"
                    >
                    <div class="field-error" id="emailError"></div>
                    <div class="field-success" id="emailSuccess">✓ Email format valid</div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Create a strong password"
                    >
                    
                    <div class="password-strength">
                        <div class="strength-meter">
                            <div class="strength-meter-fill" id="strengthMeter"></div>
                        </div>
                        <div class="strength-text" id="strengthText">Enter a password</div>
                    </div>
                    
                    <div class="password-requirements">
                        <strong>Password must contain:</strong>
                        <ul id="requirements">
                            <li id="req-length">At least 8 characters</li>
                            <li id="req-uppercase">One uppercase letter</li>
                            <li id="req-lowercase">One lowercase letter</li>
                            <li id="req-number">One number</li>
                            <li id="req-special">One special character</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        required 
                        placeholder="Re-enter your password"
                    >
                    <div class="field-error" id="confirmError"></div>
                    <div class="field-success" id="confirmSuccess">✓ Passwords match</div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn" disabled>
                    Create Account
                </button>
                
                <div class="terms">
                    By signing up, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </div>
            </form>
            
            <div class="form-footer">
                Already have an account? <a href="login-page.php">Sign in</a>
            </div>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('registerForm');
        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const passwordConfirm = document.getElementById('password_confirm');
        const submitBtn = document.getElementById('submitBtn');
        const strengthMeter = document.getElementById('strengthMeter');
        const strengthText = document.getElementById('strengthText');
        
        let validations = {
            username: false,
            email: false,
            password: false,
            confirm: false
        };
        
        // Username validation
        username.addEventListener('input', function() {
            const value = this.value.trim();
            const error = document.getElementById('usernameError');
            const success = document.getElementById('usernameSuccess');
            
            if (value.length === 0) {
                this.classList.remove('error', 'success');
                error.style.display = 'none';
                success.style.display = 'none';
                validations.username = false;
            } else if (value.length < 3) {
                this.classList.add('error');
                this.classList.remove('success');
                error.textContent = 'Username must be at least 3 characters';
                error.style.display = 'block';
                success.style.display = 'none';
                validations.username = false;
            } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                this.classList.add('error');
                this.classList.remove('success');
                error.textContent = 'Username can only contain letters, numbers, and underscores';
                error.style.display = 'block';
                success.style.display = 'none';
                validations.username = false;
            } else {
                this.classList.remove('error');
                this.classList.add('success');
                error.style.display = 'none';
                success.style.display = 'block';
                validations.username = true;
            }
            
            updateSubmitButton();
        });
        
        // Email validation
        email.addEventListener('input', function() {
            const value = this.value.trim();
            const error = document.getElementById('emailError');
            const success = document.getElementById('emailSuccess');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value.length === 0) {
                this.classList.remove('error', 'success');
                error.style.display = 'none';
                success.style.display = 'none';
                validations.email = false;
            } else if (!emailRegex.test(value)) {
                this.classList.add('error');
                this.classList.remove('success');
                error.textContent = 'Please enter a valid email address';
                error.style.display = 'block';
                success.style.display = 'none';
                validations.email = false;
            } else {
                this.classList.remove('error');
                this.classList.add('success');
                error.style.display = 'none';
                success.style.display = 'block';
                validations.email = true;
            }
            
            updateSubmitButton();
        });
        
        // Password validation
        password.addEventListener('input', function() {
            const value = this.value;
            const requirements = {
                length: value.length >= 8,
                uppercase: /[A-Z]/.test(value),
                lowercase: /[a-z]/.test(value),
                number: /[0-9]/.test(value),
                special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(value)
            };
            
            // Update requirement indicators
            document.getElementById('req-length').classList.toggle('valid', requirements.length);
            document.getElementById('req-uppercase').classList.toggle('valid', requirements.uppercase);
            document.getElementById('req-lowercase').classList.toggle('valid', requirements.lowercase);
            document.getElementById('req-number').classList.toggle('valid', requirements.number);
            document.getElementById('req-special').classList.toggle('valid', requirements.special);
            
            // Calculate strength
            const passed = Object.values(requirements).filter(v => v).length;
            
            if (value.length === 0) {
                strengthMeter.className = 'strength-meter-fill';
                strengthText.textContent = 'Enter a password';
                this.classList.remove('error', 'success');
                validations.password = false;
            } else if (passed <= 2) {
                strengthMeter.className = 'strength-meter-fill weak';
                strengthText.textContent = 'Password strength: Weak';
                this.classList.add('error');
                this.classList.remove('success');
                validations.password = false;
            } else if (passed <= 4) {
                strengthMeter.className = 'strength-meter-fill medium';
                strengthText.textContent = 'Password strength: Medium';
                this.classList.remove('error');
                this.classList.add('success');
                validations.password = passed === 5;
            } else {
                strengthMeter.className = 'strength-meter-fill strong';
                strengthText.textContent = 'Password strength: Strong';
                this.classList.remove('error');
                this.classList.add('success');
                validations.password = true;
            }
            
            // Re-validate confirm password
            if (passwordConfirm.value.length > 0) {
                passwordConfirm.dispatchEvent(new Event('input'));
            }
            
            updateSubmitButton();
        });
        
        // Confirm password validation
        passwordConfirm.addEventListener('input', function() {
            const value = this.value;
            const error = document.getElementById('confirmError');
            const success = document.getElementById('confirmSuccess');
            
            if (value.length === 0) {
                this.classList.remove('error', 'success');
                error.style.display = 'none';
                success.style.display = 'none';
                validations.confirm = false;
            } else if (value !== password.value) {
                this.classList.add('error');
                this.classList.remove('success');
                error.textContent = 'Passwords do not match';
                error.style.display = 'block';
                success.style.display = 'none';
                validations.confirm = false;
            } else {
                this.classList.remove('error');
                this.classList.add('success');
                error.style.display = 'none';
                success.style.display = 'block';
                validations.confirm = true;
            }
            
            updateSubmitButton();
        });
        
        function updateSubmitButton() {
            const allValid = Object.values(validations).every(v => v);
            submitBtn.disabled = !allValid;
        }
        
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span>Creating account...';
        });
    </script>
</body>
</html>
