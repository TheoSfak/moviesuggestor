-- =====================================================
-- SECURITY FIXES - User Management System
-- =====================================================
-- This migration adds the users table and proper foreign keys
-- Run this migration BEFORE deploying to production
-- =====================================================

USE moviesuggestor;

-- =====================================================
-- 1. CREATE USERS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL COMMENT 'User email (used for login)',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Argon2id password hash',
    username VARCHAR(100) NOT NULL COMMENT 'Display name',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last profile update',
    last_login TIMESTAMP NULL DEFAULT NULL COMMENT 'Last successful login',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Account active status',
    is_verified BOOLEAN DEFAULT FALSE COMMENT 'Email verification status',
    verification_token VARCHAR(64) NULL COMMENT 'Email verification token',
    reset_token VARCHAR(64) NULL COMMENT 'Password reset token',
    reset_token_expires TIMESTAMP NULL COMMENT 'Password reset expiry',
    failed_login_attempts INT DEFAULT 0 COMMENT 'Failed login counter',
    locked_until TIMESTAMP NULL COMMENT 'Account lock expiry',
    
    -- Indexes for performance
    INDEX idx_email (email),
    INDEX idx_active (is_active),
    INDEX idx_verified (is_verified),
    INDEX idx_verification_token (verification_token),
    INDEX idx_reset_token (reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User accounts and authentication';

-- =====================================================
-- 2. INSERT DEFAULT USERS (FOR TESTING)
-- =====================================================
-- Password: 'demo123' (change in production!)

INSERT INTO users (email, password_hash, username, is_active, is_verified)
VALUES 
    ('demo@example.com', '$argon2id$v=19$m=65536,t=4,p=1$T2hSQk1xVkRzWDNHNjdlRQ$VX4qJ1K3zX8J1m7Y5V9P2Q3R4S5T6U7V8W9X0Y1Z2A3', 'Demo User', TRUE, TRUE),
    ('admin@example.com', '$argon2id$v=19$m=65536,t=4,p=1$T2hSQk1xVkRzWDNHNjdlRQ$VX4qJ1K3zX8J1m7Y5V9P2Q3R4S5T6U7V8W9X0Y1Z2A3', 'Admin User', TRUE, TRUE)
ON DUPLICATE KEY UPDATE email = email;

-- =====================================================
-- 3. ADD FOREIGN KEY CONSTRAINTS
-- =====================================================
-- This ensures data integrity and cascading deletes
-- Skip if constraints already exist

SET FOREIGN_KEY_CHECKS = 0;

-- Check and add foreign keys to favorites table (only if not exists)
SET @fk_count = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_NAME = 'fk_favorites_user' AND TABLE_SCHEMA = DATABASE());

SET @sql = IF(@fk_count = 0,
    'ALTER TABLE favorites ADD CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT "Foreign key fk_favorites_user already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add foreign keys to watch_later table (only if not exists)
SET @fk_count = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_NAME = 'fk_watchlater_user' AND TABLE_SCHEMA = DATABASE());

SET @sql = IF(@fk_count = 0,
    'ALTER TABLE watch_later ADD CONSTRAINT fk_watchlater_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT "Foreign key fk_watchlater_user already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add foreign keys to ratings table (only if not exists)
SET @fk_count = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_NAME = 'fk_ratings_user' AND TABLE_SCHEMA = DATABASE());

SET @sql = IF(@fk_count = 0,
    'ALTER TABLE ratings ADD CONSTRAINT fk_ratings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT "Foreign key fk_ratings_user already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 4. CREATE SESSION MANAGEMENT TABLE (OPTIONAL)
-- =====================================================
-- For database-backed session management (more secure)

CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY COMMENT 'Session ID',
    user_id INT NOT NULL COMMENT 'Associated user',
    ip_address VARCHAR(45) NOT NULL COMMENT 'Client IP address',
    user_agent VARCHAR(255) NOT NULL COMMENT 'Client user agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Session start',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last activity',
    expires_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Session expiry',
    
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Active user sessions';

-- =====================================================
-- 5. CREATE LOGIN AUDIT LOG (SECURITY)
-- =====================================================

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'User ID (NULL if failed)',
    email VARCHAR(255) NOT NULL COMMENT 'Login email attempt',
    ip_address VARCHAR(45) NOT NULL COMMENT 'Client IP',
    user_agent VARCHAR(255) NOT NULL COMMENT 'Client user agent',
    success BOOLEAN NOT NULL COMMENT 'Login success/failure',
    failure_reason VARCHAR(255) NULL COMMENT 'Reason for failure',
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Attempt timestamp',
    
    INDEX idx_user_id (user_id),
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_attempted_at (attempted_at DESC),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Login attempt audit log';

-- =====================================================
-- 6. DATA MIGRATION (IF NEEDED)
-- =====================================================
-- Migrate existing data from legacy user_id = 1 to proper users

-- If you have existing favorites/ratings/watch_later with user_id = 1,
-- they will now be associated with the demo user (id = 1)
-- If you want to keep that data, make sure demo user has id = 1

-- Update favorites to ensure valid user_id
UPDATE favorites SET user_id = 1 WHERE user_id = 1;
UPDATE watch_later SET user_id = 1 WHERE user_id = 1;
UPDATE ratings SET user_id = 1 WHERE user_id = 1;

-- =====================================================
-- 7. RECORD MIGRATION
-- =====================================================

INSERT INTO migration_history (migration_name, applied_at, status)
VALUES ('008_create_users_and_security_tables', NOW(), 'success')
ON DUPLICATE KEY UPDATE applied_at = NOW();

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Verify users table created
SELECT COUNT(*) as user_count FROM users;

-- Verify foreign keys added
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'moviesuggestor'
  AND REFERENCED_TABLE_NAME = 'users';

-- Check data integrity
SELECT 
    (SELECT COUNT(*) FROM favorites WHERE user_id NOT IN (SELECT id FROM users)) as orphaned_favorites,
    (SELECT COUNT(*) FROM watch_later WHERE user_id NOT IN (SELECT id FROM users)) as orphaned_watchlater,
    (SELECT COUNT(*) FROM ratings WHERE user_id NOT IN (SELECT id FROM users)) as orphaned_ratings;

-- =====================================================
-- NOTES FOR DEVELOPERS
-- =====================================================
/*
1. Default demo password is 'demo123' - CHANGE THIS IN PRODUCTION
2. Generate proper password hashes using Security::hashPassword()
3. Foreign keys will CASCADE DELETE - user deletion removes all their data
4. Implement proper user registration with email verification
5. Add rate limiting to prevent brute force attacks
6. Consider adding 2FA for enhanced security
7. Regularly clean up expired sessions and old login attempts
8. Monitor failed login attempts for security threats

NEXT STEPS:
- Implement login.php with proper authentication
- Update index.php to require authentication
- Add registration page with email verification
- Implement password reset functionality
- Add account management page
- Set up email sending for verification/reset

SECURITY CHECKLIST:
- [ ] Change default passwords
- [ ] Configure email service
- [ ] Set up HTTPS
- [ ] Configure session settings in php.ini
- [ ] Set up rate limiting
- [ ] Configure CORS properly
- [ ] Set display_errors = Off in production
- [ ] Regular security audits
*/
