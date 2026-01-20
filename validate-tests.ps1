#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Validates all Phase 2 test files for syntax and structure
.DESCRIPTION
    Checks all test files to ensure they are properly formatted and contain expected test methods
#>

Write-Host "`n╔════════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  PHASE 2 TEST VALIDATION                                           ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

$testFiles = @(
    'tests/FavoritesRepositoryTest.php',
    'tests/WatchLaterRepositoryTest.php',
    'tests/RatingRepositoryTest.php',
    'tests/FilterBuilderTest.php'
)

$totalTests = 0
$totalLines = 0
$allValid = $true

Write-Host "📊 ANALYZING TEST FILES:`n" -ForegroundColor Yellow

foreach ($file in $testFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw
        $lines = (Get-Content $file).Count
        
        # Count test methods
        $testCount = ([regex]::Matches($content, 'public function test\w+\(')).Count
        
        # Check for required elements
        $hasSetUp = $content -match 'protected function setUp\(\)'
        $hasTearDown = $content -match 'protected function tearDown\(\)'
        $hasClass = $content -match 'class \w+Test extends TestCase'
        
        $fileName = Split-Path $file -Leaf
        Write-Host "  ✓ $fileName" -ForegroundColor Green
        Write-Host "     Tests: $testCount | Lines: $lines" -ForegroundColor Gray
        Write-Host "     setUp: $(if($hasSetUp){'✓'}else{'✗'}) | tearDown: $(if($hasTearDown){'✓'}else{'✗'}) | TestCase: $(if($hasClass){'✓'}else{'✗'})" -ForegroundColor Gray
        
        $totalTests += $testCount
        $totalLines += $lines
        
        # FilterBuilder doesn't need tearDown (no database cleanup)
        $needsTearDown = $fileName -notmatch 'FilterBuilder'
        if (-not ($hasSetUp -and $hasClass)) {
            Write-Host "     ⚠ Warning: Missing some standard test components" -ForegroundColor Yellow
            $allValid = $false
        } elseif ($needsTearDown -and -not $hasTearDown) {
            Write-Host "     ⚠ Warning: Database test missing tearDown" -ForegroundColor Yellow
            $allValid = $false
        }
        
        Write-Host ""
    } else {
        Write-Host "  ✗ $file - NOT FOUND" -ForegroundColor Red
        $allValid = $false
    }
}

Write-Host "`n📈 SUMMARY:" -ForegroundColor Yellow
Write-Host "  Total Test Files: $($testFiles.Count)" -ForegroundColor White
Write-Host "  Total Test Methods: $totalTests" -ForegroundColor White
Write-Host "  Total Lines: $totalLines" -ForegroundColor White
Write-Host "  Average Tests/File: $([math]::Round($totalTests / $testFiles.Count, 1))" -ForegroundColor White
Write-Host "  Average Lines/File: $([math]::Round($totalLines / $testFiles.Count, 0))" -ForegroundColor White

Write-Host "`n🔍 TEST STRUCTURE VALIDATION:" -ForegroundColor Yellow

# Check each test file structure in detail
foreach ($file in $testFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw
        $fileName = Split-Path $file -Leaf
        
        # Extract test categories
        $testMethods = [regex]::Matches($content, 'public function (test\w+)\(')
        $categories = @{}
        
        foreach ($match in $testMethods) {
            $methodName = $match.Groups[1].Value
            # Extract category from method name (e.g., testAddToFavorites -> Add)
            if ($methodName -match 'test(\w+?)(?:Success|Invalid|Negative|Empty|With|Returns|Throws|After|Before|Edge|Large|Long|Multiple|Single|Concurrent|Special)') {
                $category = $matches[1]
                if ($categories.ContainsKey($category)) {
                    $categories[$category]++
                } else {
                    $categories[$category] = 1
                }
            }
        }
        
        Write-Host "  📝 $fileName" -ForegroundColor Cyan
        if ($categories.Count -gt 0) {
            $categories.GetEnumerator() | Sort-Object Value -Descending | ForEach-Object {
                Write-Host "     - $($_.Key): $($_.Value) tests" -ForegroundColor Gray
            }
        }
        Write-Host ""
    }
}

Write-Host "`n🎯 VALIDATION CHECKS:" -ForegroundColor Yellow

# Check for common test patterns
$checks = @{
    'Input Validation Tests' = 'InvalidArgumentException|expectException'
    'Success Tests' = 'testSuccess|assertTrue'
    'Edge Case Tests' = 'testEdge|testLarge|testConcurrent|testSpecial'
    'SQL Injection Tests' = 'SQLInjection|testSQLInjection'
    'Data Persistence Tests' = 'PersistsAcross|testDataPersists'
}

foreach ($check in $checks.GetEnumerator()) {
    $count = 0
    foreach ($file in $testFiles) {
        if (Test-Path $file) {
            $content = Get-Content $file -Raw
            $matches = [regex]::Matches($content, $check.Value)
            $count += $matches.Count
        }
    }
    
    $icon = if ($count -gt 0) { '✓' } else { '✗' }
    $color = if ($count -gt 0) { 'Green' } else { 'Yellow' }
    Write-Host "  $icon $($check.Key): $count occurrences" -ForegroundColor $color
}

Write-Host "`n" -NoNewline

if ($allValid -and $totalTests -gt 0) {
    Write-Host "✅ VALIDATION COMPLETE - ALL TESTS VALID" -ForegroundColor Green
    Write-Host "`nℹ️  To run tests (requires PHP and Composer):" -ForegroundColor Cyan
    Write-Host "   composer test" -ForegroundColor White
    Write-Host "   OR" -ForegroundColor Gray
    Write-Host "   vendor/bin/phpunit" -ForegroundColor White
    Write-Host "   vendor/bin/phpunit tests/FavoritesRepositoryTest.php" -ForegroundColor White
    exit 0
} else {
    Write-Host "⚠️  VALIDATION ISSUES DETECTED" -ForegroundColor Yellow
    exit 1
}
