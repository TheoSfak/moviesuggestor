# 🔐 Authentication System

Complete user authentication system for Movie Suggestor application.

## Features

### ✅ User Registration
- **Location**: `register-page.php`
- Email and username validation
- Real-time password strength indicator
- Password requirements validation:
  - Minimum 8 characters
  - At least 1 uppercase letter
  - At least 1 lowercase letter
  - At least 1 number
  - At least 1 special character
- Confirm password matching
- Argon2id password hashing

### ✅ User Login
- **Location**: `login-page.php`
- Email/password authentication
- Rate limiting (5 attempts per 5 minutes)
- Account lockout after 5 failed attempts
- Remember me functionality
- Demo credentials provided
- Login attempt audit logging

### ✅ User Profile
- **Location**: `profile.php`
- User statistics dashboard
  - Favorite movies count
  - Watch later list count
  - Watched movies count
  - Ratings given count
- Recent activity feed
- Account information display
- Quick links to settings

### ✅ Password Reset
- **Location**: `forgot-password.php`
- Email-based password reset flow
- Secure token generation
- Link expiration (1 hour)
- Email verification

## File Structure

```
auth/
├── login-page.php          # Modern login interface
├── register-page.php       # Registration with validation
├── profile.php             # User profile dashboard
├── forgot-password.php     # Password reset flow
└── README.md              # This file

Backend Integration:
├── ../login.php            # Login processing
├── ../register.php         # Registration processing
├── ../logout.php           # Session cleanup
└── ../src/Security.php     # Security helper class
```

## Usage

### Access the Authentication Pages

1. **Login**: `http://localhost/moviesuggestor/auth/login-page.php`
2. **Register**: `http://localhost/moviesuggestor/auth/register-page.php`
3. **Profile**: `http://localhost/moviesuggestor/auth/profile.php` (requires login)
4. **Forgot Password**: `http://localhost/moviesuggestor/auth/forgot-password.php`

### Demo Credentials

For testing purposes:
- **Email**: `demo@example.com`
- **Password**: `demo123`

Or use:
- **Email**: `admin@example.com`
- **Password**: `demo123`

## Security Features

### Password Security
- ✅ Argon2id hashing algorithm (memory-hard)
- ✅ Minimum strength requirements enforced
- ✅ Real-time strength indicator
- ✅ No plain-text storage

### Session Security
- ✅ 30-minute timeout with activity tracking
- ✅ Session ID regeneration on login
- ✅ HttpOnly cookies (prevents XSS theft)
- ✅ Secure flag (HTTPS only)
- ✅ SameSite=Lax (CSRF protection)

### Rate Limiting
- ✅ Login: 5 attempts per 5 minutes
- ✅ Account lockout: 5 failures = 30-minute lock
- ✅ IP-based tracking
- ✅ Audit trail in database

### CSRF Protection
- ✅ Cryptographically secure tokens (32 bytes)
- ✅ Timing-safe comparison
- ✅ Token regeneration on logout
- ✅ Required for all state changes

### Input Validation
- ✅ Email format validation
- ✅ Username format validation (alphanumeric + underscore)
- ✅ Password strength validation
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (output sanitization)

## Integration with Main Application

### Protecting Pages

```php
<?php
require_once __DIR__ . '/src/Security.php';
use MovieSuggestor\Security;

// Initialize session and require authentication
Security::initSession();
Security::requireAuth();

// Get authenticated user ID (NEVER trust client input)
$userId = Security::getUserId();

// Your protected code here
?>
```

### Protecting API Endpoints

```php
<?php
require_once __DIR__ . '/../src/Security.php';
use MovieSuggestor\Security;

// Initialize and authenticate
Security::initSession();
Security::requireAuth();
$userId = Security::getUserId();

// Require CSRF token for state-changing operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCSRFToken();
}

// API logic here
?>
```

### Redirect to Login

The `Security::requireAuth()` method automatically redirects unauthenticated users to the login page. Configure the redirect URL in `src/Security.php`:

```php
public static function requireAuth(): void
{
    if (!self::isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /moviesuggestor/auth/login-page.php');
        exit;
    }
}
```

## UI/UX Features

### Modern Design
- Gradient backgrounds
- Card-based layouts
- Smooth animations
- Responsive design (mobile-friendly)
- Accessibility features

### Real-time Validation
- Instant feedback on input
- Visual indicators (✓ success, ✗ error)
- Password strength meter
- Requirement checklist

### User Feedback
- Success messages
- Error messages
- Loading states
- Progress indicators

### Keyboard Shortcuts
- `Ctrl+D` on login page auto-fills demo credentials
- Standard form navigation with Tab
- Enter to submit forms

## Customization

### Styling
Each page has embedded CSS for easy customization. Colors, fonts, and layouts can be modified directly in the `<style>` tags.

**Primary Colors**:
- Login: `#667eea` to `#764ba2` (purple gradient)
- Register: `#f093fb` to `#f5576c` (pink gradient)
- Success: `#2ecc71` (green)
- Error: `#e74c3c` (red)
- Info: `#1976d2` (blue)

### Validation Rules
Password requirements can be adjusted in `register-page.php`:

```javascript
const requirements = {
    length: value.length >= 8,      // Minimum length
    uppercase: /[A-Z]/.test(value), // Uppercase required
    lowercase: /[a-z]/.test(value), // Lowercase required
    number: /[0-9]/.test(value),    // Number required
    special: /[!@#$%^&*...]/.test(value) // Special char required
};
```

## Database Schema

The authentication system uses the following tables:

### users
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(64),
    reset_token VARCHAR(64),
    reset_token_expires DATETIME,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME
);
```

### user_sessions
```sql
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### login_attempts
```sql
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    successful BOOLEAN NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Testing

### Manual Testing Checklist

#### Registration Flow
- [ ] Register with valid credentials
- [ ] Try weak password (should be rejected)
- [ ] Try duplicate email (should be rejected)
- [ ] Try invalid email format (should be rejected)
- [ ] Verify password confirmation matching
- [ ] Check auto-login after registration

#### Login Flow
- [ ] Login with correct credentials
- [ ] Login with wrong password (should show error)
- [ ] Try 5 wrong passwords (should lock account)
- [ ] Wait 30 minutes and try again
- [ ] Test "Remember Me" checkbox
- [ ] Verify redirect to original page after login

#### Profile Page
- [ ] View user statistics
- [ ] Check recent activity display
- [ ] Verify all counts are accurate
- [ ] Test navigation links

#### Security
- [ ] Verify CSRF token in forms
- [ ] Test rate limiting on login
- [ ] Verify session timeout (30 minutes)
- [ ] Check HttpOnly cookie flag
- [ ] Test logout cleanup

## Troubleshooting

### "Session already started" warning
Make sure `Security::initSession()` is called before any output.

### Login redirects to wrong page
Check the `redirect_after_login` session variable configuration.

### Password hash not recognized
Ensure Argon2id is available in your PHP installation (PHP 7.2+).

### Rate limiting not working
Verify that sessions are working correctly and not being destroyed prematurely.

### Styles not loading
Make sure CSS is embedded in each PHP file or link external CSS properly.

## Production Deployment

Before deploying to production:

1. **Enable HTTPS**
   - Obtain SSL/TLS certificate
   - Update session cookie settings

2. **Configure Email**
   - Set up SMTP for password reset emails
   - Update email templates

3. **Environment Variables**
   - Set `APP_ENV=production`
   - Configure database credentials
   - Set TMDB API key

4. **Security Hardening**
   - Remove demo credentials
   - Disable error display
   - Enable error logging
   - Configure firewall

5. **Performance**
   - Enable OPcache
   - Set up Redis for sessions
   - Configure CDN for assets

## Support

For issues or questions:
1. Check [SECURITY_AUDIT_REPORT.md](../SECURITY_AUDIT_REPORT.md)
2. Review [PHASE1_FINAL_REPORT.md](../PHASE1_FINAL_REPORT.md)
3. Consult [src/Security.php](../src/Security.php) documentation

## License

Part of Movie Suggestor project. All rights reserved.

---

**Version**: 1.0  
**Last Updated**: January 21, 2026  
**Status**: Production Ready ✅
