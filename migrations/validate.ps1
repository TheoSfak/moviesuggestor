# Migration Validation Script
# Validates SQL migration files for common issues

Write-Host "`n=== Validating Migration Files ===" -ForegroundColor Cyan
Write-Host ""

$migrationsDir = "migrations"
$issues = @()
$validated = 0

# Define expected migrations
$migrations = @(
    "001_add_movie_metadata",
    "002_create_favorites_table",
    "003_create_watch_later_table",
    "004_create_ratings_table",
    "005_create_indexes"
)

foreach ($migration in $migrations) {
    Write-Host "Validating: $migration" -ForegroundColor Yellow
    
    # Check UP migration
    $upFile = Join-Path $migrationsDir "$migration.sql"
    if (Test-Path $upFile) {
        $content = Get-Content $upFile -Raw
        if ($content.Length -gt 100) {
            # Check for required keywords
            if ($content -match "BEGIN;") {
                Write-Host "  [OK] UP migration file valid (BEGIN transaction found)" -ForegroundColor Green
                $validated++
            } else {
                Write-Host "  [WARN] UP migration missing BEGIN statement" -ForegroundColor Yellow
                $issues += "$migration.sql: Missing BEGIN transaction"
            }
            
            # Check for SQL injection risks (commented code)
            if ($content -match "/\*.*\*/" -and $content.Length -lt 500) {
                Write-Host "  [WARN] UP migration appears to be commented out" -ForegroundColor Yellow
                $issues += "$migration.sql: Contains block comments"
            }
        } else {
            Write-Host "  [ERROR] UP migration file is too small or empty" -ForegroundColor Red
            $issues += "$migration.sql: File too small"
        }
    } else {
        Write-Host "  [ERROR] UP migration file not found" -ForegroundColor Red
        $issues += "$migration.sql: File missing"
    }
    
    # Check DOWN migration
    $downFile = Join-Path $migrationsDir "${migration}_down.sql"
    if (Test-Path $downFile) {
        $content = Get-Content $downFile -Raw
        if ($content.Length -gt 50) {
            if ($content -match "BEGIN;") {
                Write-Host "  [OK] DOWN migration file valid (BEGIN transaction found)" -ForegroundColor Green
                $validated++
            } else {
                Write-Host "  [WARN] DOWN migration missing BEGIN statement" -ForegroundColor Yellow
                $issues += "${migration}_down.sql: Missing BEGIN transaction"
            }
        } else {
            Write-Host "  [ERROR] DOWN migration file is too small or empty" -ForegroundColor Red
            $issues += "${migration}_down.sql: File too small"
        }
    } else {
        Write-Host "  [ERROR] DOWN migration file not found" -ForegroundColor Red
        $issues += "${migration}_down.sql: File missing"
    }
    
    Write-Host ""
}

# Check for migration runner
Write-Host "Validating: run-migrations.php" -ForegroundColor Yellow
$runnerFile = Join-Path $migrationsDir "run-migrations.php"
if (Test-Path $runnerFile) {
    $size = (Get-Item $runnerFile).Length
    if ($size -gt 5000) {
        Write-Host "  [OK] Migration runner script valid ($size bytes)" -ForegroundColor Green
        $validated++
    } else {
        Write-Host "  [WARN] Migration runner script seems incomplete" -ForegroundColor Yellow
        $issues += "run-migrations.php: File may be incomplete"
    }
} else {
    Write-Host "  [ERROR] Migration runner script not found" -ForegroundColor Red
    $issues += "run-migrations.php: File missing"
}

Write-Host ""
Write-Host "=== Validation Summary ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Total files validated: $validated" -ForegroundColor White
Write-Host "Issues found: $($issues.Count)" -ForegroundColor $(if ($issues.Count -eq 0) { "Green" } else { "Yellow" })

if ($issues.Count -gt 0) {
    Write-Host "`nIssues:" -ForegroundColor Yellow
    foreach ($issue in $issues) {
        Write-Host "  - $issue" -ForegroundColor Yellow
    }
} else {
    Write-Host "`n✓ All migration files validated successfully!" -ForegroundColor Green
}

Write-Host ""

# Check SQL syntax by looking for common patterns
Write-Host "=== SQL Syntax Check ===" -ForegroundColor Cyan
Write-Host ""

$sqlFiles = Get-ChildItem "$migrationsDir/*.sql" -Exclude "000_*.sql"
$syntaxOk = $true

foreach ($file in $sqlFiles) {
    $content = Get-Content $file.FullName -Raw
    
    # Check for balanced BEGIN/COMMIT
    $beginCount = ([regex]::Matches($content, "BEGIN;")).Count
    $commitCount = ([regex]::Matches($content, "COMMIT;")).Count
    
    if ($beginCount -ne $commitCount) {
        Write-Host "  [ERROR] $($file.Name): Unbalanced BEGIN/COMMIT" -ForegroundColor Red
        $syntaxOk = $false
    }
    
    # Check for common SQL keywords
    if ($content -match "(CREATE|ALTER|DROP|INSERT)\s+(TABLE|INDEX|CONSTRAINT)") {
        Write-Host "  [OK] $($file.Name): Valid SQL statements found" -ForegroundColor Green
    } else {
        Write-Host "  [WARN] $($file.Name): No SQL statements detected" -ForegroundColor Yellow
        $syntaxOk = $false
    }
}

Write-Host ""
if ($syntaxOk -and $issues.Count -eq 0) {
    Write-Host "✓✓✓ All validations passed! Migrations are ready to run. ✓✓✓" -ForegroundColor Green
} else {
    Write-Host "⚠ Please review the warnings and errors above." -ForegroundColor Yellow
}

Write-Host ""
