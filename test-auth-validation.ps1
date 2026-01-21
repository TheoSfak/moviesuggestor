# Authentication System Validation Script
# Tests all auth endpoints and verifies they're accessible

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "AUTHENTICATION SYSTEM VALIDATION" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$baseUrl = "http://localhost/moviesuggestor"
$tests = @()

function Test-URL {
    param(
        [string]$url,
        [string]$name
    )
    
    try {
        $response = Invoke-WebRequest -Uri $url -Method Get -UseBasicParsing -TimeoutSec 5
        $statusCode = $response.StatusCode
        
        if ($statusCode -eq 200) {
            Write-Host "✓ PASS: $name" -ForegroundColor Green
            Write-Host "  URL: $url" -ForegroundColor Gray
            Write-Host "  Status: $statusCode OK`n" -ForegroundColor Gray
            return @{
                Name = $name
                URL = $url
                Status = "PASS"
                StatusCode = $statusCode
            }
        } else {
            Write-Host "✗ FAIL: $name" -ForegroundColor Red
            Write-Host "  URL: $url" -ForegroundColor Gray
            Write-Host "  Status: $statusCode`n" -ForegroundColor Red
            return @{
                Name = $name
                URL = $url
                Status = "FAIL"
                StatusCode = $statusCode
            }
        }
    } catch {
        Write-Host "✗ ERROR: $name" -ForegroundColor Red
        Write-Host "  URL: $url" -ForegroundColor Gray
        Write-Host "  Error: $($_.Exception.Message)`n" -ForegroundColor Red
        return @{
            Name = $name
            URL = $url
            Status = "ERROR"
            StatusCode = 0
            Error = $_.Exception.Message
        }
    }
}

Write-Host "Testing Core Pages..." -ForegroundColor Yellow
Write-Host "─────────────────────`n" -ForegroundColor Gray

$tests += Test-URL "$baseUrl/test-auth.php" "Authentication Test Page"
$tests += Test-URL "$baseUrl/index.php" "Main Application"

Write-Host "Testing Authentication Pages..." -ForegroundColor Yellow
Write-Host "────────────────────────────────`n" -ForegroundColor Gray

$tests += Test-URL "$baseUrl/auth/login-page.php" "Login Page"
$tests += Test-URL "$baseUrl/auth/register-page.php" "Registration Page"
$tests += Test-URL "$baseUrl/auth/profile.php" "User Profile (should redirect)"
$tests += Test-URL "$baseUrl/auth/forgot-password.php" "Forgot Password Page"

Write-Host "Testing Backend Handlers..." -ForegroundColor Yellow
Write-Host "────────────────────────────`n" -ForegroundColor Gray

# These will return 405 or redirect, but should not 404
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/login.php" -Method Get -UseBasicParsing -TimeoutSec 5 -MaximumRedirection 0 -ErrorAction SilentlyContinue
    Write-Host "✓ PASS: Login Handler" -ForegroundColor Green
    Write-Host "  Status: $($response.StatusCode)`n" -ForegroundColor Gray
    $tests += @{ Name = "Login Handler"; Status = "PASS"; StatusCode = $response.StatusCode }
} catch {
    if ($_.Exception.Response.StatusCode -eq 302) {
        Write-Host "✓ PASS: Login Handler (redirect)" -ForegroundColor Green
        Write-Host "  Status: 302 Redirect`n" -ForegroundColor Gray
        $tests += @{ Name = "Login Handler"; Status = "PASS"; StatusCode = 302 }
    } else {
        Write-Host "✗ FAIL: Login Handler" -ForegroundColor Red
        Write-Host "  Error: $($_.Exception.Message)`n" -ForegroundColor Red
        $tests += @{ Name = "Login Handler"; Status = "FAIL"; StatusCode = 0 }
    }
}

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/register.php" -Method Get -UseBasicParsing -TimeoutSec 5 -MaximumRedirection 0 -ErrorAction SilentlyContinue
    Write-Host "✓ PASS: Register Handler" -ForegroundColor Green
    Write-Host "  Status: $($response.StatusCode)`n" -ForegroundColor Gray
    $tests += @{ Name = "Register Handler"; Status = "PASS"; StatusCode = $response.StatusCode }
} catch {
    if ($_.Exception.Response.StatusCode -eq 302) {
        Write-Host "✓ PASS: Register Handler (redirect)" -ForegroundColor Green
        Write-Host "  Status: 302 Redirect`n" -ForegroundColor Gray
        $tests += @{ Name = "Register Handler"; Status = "PASS"; StatusCode = 302 }
    } else {
        Write-Host "✗ FAIL: Register Handler" -ForegroundColor Red
        Write-Host "  Error: $($_.Exception.Message)`n" -ForegroundColor Red
        $tests += @{ Name = "Register Handler"; Status = "FAIL"; StatusCode = 0 }
    }
}

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/logout.php" -Method Get -UseBasicParsing -TimeoutSec 5 -MaximumRedirection 0 -ErrorAction SilentlyContinue
    Write-Host "✓ PASS: Logout Handler" -ForegroundColor Green
    Write-Host "  Status: $($response.StatusCode)`n" -ForegroundColor Gray
    $tests += @{ Name = "Logout Handler"; Status = "PASS"; StatusCode = $response.StatusCode }
} catch {
    if ($_.Exception.Response.StatusCode -eq 302) {
        Write-Host "✓ PASS: Logout Handler (redirect)" -ForegroundColor Green
        Write-Host "  Status: 302 Redirect`n" -ForegroundColor Gray
        $tests += @{ Name = "Logout Handler"; Status = "PASS"; StatusCode = 302 }
    } else {
        Write-Host "✗ FAIL: Logout Handler" -ForegroundColor Red
        Write-Host "  Error: $($_.Exception.Message)`n" -ForegroundColor Red
        $tests += @{ Name = "Logout Handler"; Status = "FAIL"; StatusCode = 0 }
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "TEST SUMMARY" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$passed = ($tests | Where-Object { $_.Status -eq "PASS" }).Count
$failed = ($tests | Where-Object { $_.Status -eq "FAIL" }).Count
$errors = ($tests | Where-Object { $_.Status -eq "ERROR" }).Count
$total = $tests.Count

Write-Host "Total Tests: $total" -ForegroundColor White
Write-Host "Passed:      $passed" -ForegroundColor Green
Write-Host "Failed:      $failed" -ForegroundColor $(if ($failed -gt 0) { "Red" } else { "Gray" })
Write-Host "Errors:      $errors" -ForegroundColor $(if ($errors -gt 0) { "Red" } else { "Gray" })

$passRate = [math]::Round(($passed / $total) * 100, 1)
Write-Host "`nPass Rate:   $passRate%" -ForegroundColor $(if ($passRate -ge 90) { "Green" } elseif ($passRate -ge 70) { "Yellow" } else { "Red" })

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "QUICK ACCESS URLs" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "Main Test Page:" -ForegroundColor Yellow
Write-Host "  http://localhost/moviesuggestor/test-auth.php" -ForegroundColor Cyan

Write-Host "`nAuthentication Pages:" -ForegroundColor Yellow
Write-Host "  Login:    http://localhost/moviesuggestor/auth/login-page.php" -ForegroundColor Cyan
Write-Host "  Register: http://localhost/moviesuggestor/auth/register-page.php" -ForegroundColor Cyan
Write-Host "  Profile:  http://localhost/moviesuggestor/auth/profile.php" -ForegroundColor Cyan

Write-Host "`nMain Application:" -ForegroundColor Yellow
Write-Host "  http://localhost/moviesuggestor/index.php" -ForegroundColor Cyan

Write-Host "`nDemo Credentials:" -ForegroundColor Yellow
Write-Host "  Email:    demo@example.com" -ForegroundColor Cyan
Write-Host "  Password: demo123" -ForegroundColor Cyan

Write-Host "`n"

if ($passRate -eq 100) {
    Write-Host "🎉 ALL TESTS PASSED! Authentication system is ready to use!" -ForegroundColor Green
} elseif ($passRate -ge 80) {
    Write-Host "⚠️  Most tests passed. Review any failures above." -ForegroundColor Yellow
} else {
    Write-Host "❌ Multiple failures detected. Please review the errors above." -ForegroundColor Red
}

Write-Host ""
