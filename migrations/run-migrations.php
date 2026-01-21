<?php
/**
 * Database Migration Runner for Phase 2
 * 
 * Runs all pending database migrations in order and tracks execution.
 * Supports both "up" (apply) and "down" (rollback) migrations.
 * 
 * Usage:
 *   php migrations/run-migrations.php           # Run all pending migrations
 *   php migrations/run-migrations.php up        # Same as above
 *   php migrations/run-migrations.php down      # Rollback last migration
 *   php migrations/run-migrations.php down all  # Rollback all migrations
 *   php migrations/run-migrations.php status    # Show migration status
 *   php migrations/run-migrations.php validate  # Validate SQL syntax only
 */

require_once __DIR__ . '/../src/Database.php';

use MovieSuggestor\Database;

class MigrationRunner
{
    private $db;
    private $migrationsDir;
    
    // Define migrations in execution order
    private $migrations = [
        '001_add_movie_metadata',
        '002_create_favorites_table',
        '003_create_watch_later_table',
        '004_create_ratings_table',
        '005_create_indexes',
        '007_tmdb_integration',
        '008_create_users_and_security_tables'
    ];
    
    public function __construct()
    {
        $this->migrationsDir = __DIR__;
        $database = new Database();
        $this->db = $database->connect();
        $this->ensureMigrationTable();
    }
    
    /**
     * Ensure migration tracking table exists
     */
    private function ensureMigrationTable()
    {
        $trackingFile = $this->migrationsDir . '/000_migration_tracking.sql';
        if (file_exists($trackingFile)) {
            $sql = file_get_contents($trackingFile);
            $this->db->exec($sql);
        }
    }
    
    /**
     * Get list of applied migrations
     */
    private function getAppliedMigrations(): array
    {
        try {
            $stmt = $this->db->query("SELECT migration_name FROM migration_history WHERE status = 'success' ORDER BY applied_at");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Check if migration has been applied
     */
    private function isMigrationApplied(string $name): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM migration_history WHERE migration_name = ? AND status = 'success'");
        $stmt->execute([$name]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Record migration execution
     */
    private function recordMigration(string $name, int $executionTime, string $status = 'success', ?string $error = null)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO migration_history (migration_name, execution_time_ms, status, error_message) 
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
             applied_at = CURRENT_TIMESTAMP,
             execution_time_ms = VALUES(execution_time_ms),
             status = VALUES(status),
             error_message = VALUES(error_message)"
        );
        $stmt->execute([$name, $executionTime, $status, $error]);
    }
    
    /**
     * Run pending migrations
     */
    public function runUp(): int
    {
        $this->printHeader("Running Database Migrations");
        
        $appliedMigrations = $this->getAppliedMigrations();
        $pendingCount = 0;
        
        foreach ($this->migrations as $migration) {
            if (in_array($migration, $appliedMigrations)) {
                $this->printSkip($migration);
                continue;
            }
            
            $file = $this->migrationsDir . '/' . $migration . '.sql';
            
            if (!file_exists($file)) {
                $this->printError($migration, "File not found: $file");
                continue;
            }
            
            $this->printRunning($migration);
            
            $startTime = microtime(true);
            
            try {
                $sql = file_get_contents($file);
                $this->db->exec($sql);
                
                $executionTime = (int)((microtime(true) - $startTime) * 1000);
                $this->recordMigration($migration, $executionTime, 'success');
                
                $this->printSuccess($migration, $executionTime);
                $pendingCount++;
                
            } catch (PDOException $e) {
                $executionTime = (int)((microtime(true) - $startTime) * 1000);
                $this->recordMigration($migration, $executionTime, 'failed', $e->getMessage());
                $this->printError($migration, $e->getMessage());
                
                echo "\n❌ Migration failed. Aborting.\n";
                return 1;
            }
        }
        
        if ($pendingCount === 0) {
            echo "\n✓ No pending migrations. Database is up to date.\n";
        } else {
            echo "\n✓ All migrations completed successfully!\n";
            echo "  Applied: $pendingCount migration(s)\n";
        }
        
        return 0;
    }
    
    /**
     * Rollback migrations
     */
    public function runDown(bool $all = false): int
    {
        $this->printHeader($all ? "Rolling Back All Migrations" : "Rolling Back Last Migration");
        
        $appliedMigrations = $this->getAppliedMigrations();
        
        if (empty($appliedMigrations)) {
            echo "✓ No migrations to rollback.\n";
            return 0;
        }
        
        $toRollback = $all ? array_reverse($appliedMigrations) : [end($appliedMigrations)];
        
        foreach ($toRollback as $migration) {
            $file = $this->migrationsDir . '/' . $migration . '_down.sql';
            
            if (!file_exists($file)) {
                $this->printError($migration, "Rollback file not found: $file");
                continue;
            }
            
            $this->printRunning($migration, true);
            
            $startTime = microtime(true);
            
            try {
                $sql = file_get_contents($file);
                $this->db->exec($sql);
                
                $executionTime = (int)((microtime(true) - $startTime) * 1000);
                
                // Mark as rolled back
                $stmt = $this->db->prepare("UPDATE migration_history SET status = 'rolled_back' WHERE migration_name = ?");
                $stmt->execute([$migration]);
                
                $this->printSuccess($migration, $executionTime, true);
                
            } catch (PDOException $e) {
                $executionTime = (int)((microtime(true) - $startTime) * 1000);
                $this->printError($migration, $e->getMessage());
                
                echo "\n❌ Rollback failed. Aborting.\n";
                return 1;
            }
        }
        
        echo "\n✓ Rollback completed successfully!\n";
        return 0;
    }
    
    /**
     * Show migration status
     */
    public function showStatus()
    {
        $this->printHeader("Migration Status");
        
        $appliedMigrations = $this->getAppliedMigrations();
        
        echo "Total migrations: " . count($this->migrations) . "\n";
        echo "Applied: " . count($appliedMigrations) . "\n";
        echo "Pending: " . (count($this->migrations) - count($appliedMigrations)) . "\n\n";
        
        foreach ($this->migrations as $migration) {
            $isApplied = in_array($migration, $appliedMigrations);
            $status = $isApplied ? "✓ Applied" : "○ Pending";
            $color = $isApplied ? "\033[32m" : "\033[33m";
            echo "$color$status\033[0m  $migration\n";
        }
        
        // Show migration history
        try {
            $stmt = $this->db->query(
                "SELECT migration_name, applied_at, execution_time_ms, status 
                 FROM migration_history 
                 ORDER BY applied_at DESC 
                 LIMIT 10"
            );
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($history)) {
                echo "\n" . str_repeat("─", 70) . "\n";
                echo "Recent Migration History:\n";
                echo str_repeat("─", 70) . "\n";
                
                foreach ($history as $record) {
                    $statusColor = $record['status'] === 'success' ? "\033[32m" : 
                                 ($record['status'] === 'rolled_back' ? "\033[33m" : "\033[31m");
                    echo sprintf(
                        "%s%-25s\033[0m %s %5dms\n",
                        $statusColor,
                        $record['migration_name'],
                        $record['applied_at'],
                        $record['execution_time_ms']
                    );
                }
            }
        } catch (PDOException $e) {
            // Ignore if migration_history doesn't exist yet
        }
    }
    
    /**
     * Validate migration SQL files
     */
    public function validate(): int
    {
        $this->printHeader("Validating Migration Files");
        
        $hasErrors = false;
        
        foreach ($this->migrations as $migration) {
            $upFile = $this->migrationsDir . '/' . $migration . '.sql';
            $downFile = $this->migrationsDir . '/' . $migration . '_down.sql';
            
            echo "Checking: $migration\n";
            
            // Check up migration
            if (!file_exists($upFile)) {
                echo "  ✗ Missing UP migration file\n";
                $hasErrors = true;
            } else {
                $sql = file_get_contents($upFile);
                if (empty(trim($sql))) {
                    echo "  ✗ UP migration file is empty\n";
                    $hasErrors = true;
                } else {
                    echo "  ✓ UP migration file valid\n";
                }
            }
            
            // Check down migration
            if (!file_exists($downFile)) {
                echo "  ✗ Missing DOWN migration file\n";
                $hasErrors = true;
            } else {
                $sql = file_get_contents($downFile);
                if (empty(trim($sql))) {
                    echo "  ✗ DOWN migration file is empty\n";
                    $hasErrors = true;
                } else {
                    echo "  ✓ DOWN migration file valid\n";
                }
            }
            
            echo "\n";
        }
        
        if ($hasErrors) {
            echo "❌ Validation failed. Please fix the errors above.\n";
            return 1;
        }
        
        echo "✓ All migration files validated successfully!\n";
        return 0;
    }
    
    // Printing helpers
    
    private function printHeader(string $title)
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "  $title\n";
        echo str_repeat("=", 70) . "\n\n";
    }
    
    private function printRunning(string $migration, bool $rollback = false)
    {
        $action = $rollback ? "Rolling back" : "Running";
        echo "→ $action: $migration ... ";
    }
    
    private function printSuccess(string $migration, int $executionTime, bool $rollback = false)
    {
        $action = $rollback ? "rolled back" : "completed";
        echo "\033[32m✓\033[0m ($executionTime ms)\n";
    }
    
    private function printSkip(string $migration)
    {
        echo "⊗ Skipped: $migration (already applied)\n";
    }
    
    private function printError(string $migration, string $error)
    {
        echo "\033[31m✗ Failed\033[0m\n";
        echo "  Error: $error\n";
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

try {
    $runner = new MigrationRunner();
    
    $command = $argv[1] ?? 'up';
    $exitCode = 0;
    
    switch ($command) {
        case 'up':
            $exitCode = $runner->runUp();
            break;
            
        case 'down':
            $all = isset($argv[2]) && $argv[2] === 'all';
            $exitCode = $runner->runDown($all);
            break;
            
        case 'status':
            $runner->showStatus();
            break;
            
        case 'validate':
            $exitCode = $runner->validate();
            break;
            
        default:
            echo "Usage: php migrations/run-migrations.php [command]\n\n";
            echo "Commands:\n";
            echo "  up        Run all pending migrations (default)\n";
            echo "  down      Rollback last migration\n";
            echo "  down all  Rollback all migrations\n";
            echo "  status    Show migration status\n";
            echo "  validate  Validate migration files without running\n";
            $exitCode = 1;
    }
    
    exit($exitCode);
    
} catch (Exception $e) {
    echo "\n\033[31m✗ Error:\033[0m " . $e->getMessage() . "\n";
    exit(1);
}
