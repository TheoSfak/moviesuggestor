-- Migration Tracking Table
-- Keeps track of which migrations have been applied

CREATE TABLE IF NOT EXISTS migration_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time_ms INT DEFAULT 0,
    status ENUM('success', 'failed', 'rolled_back') DEFAULT 'success',
    error_message TEXT DEFAULT NULL,
    INDEX idx_applied_at (applied_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Migration history tracking';
