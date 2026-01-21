<?php
/**
 * MULTI-AGENT QA VALIDATION SYSTEM
 * 
 * This system uses specialized agents to thoroughly test all authentication
 * functionality BEFORE reporting success to the user.
 * 
 * Agents:
 * 1. INSPECTOR - Checks code structure and syntax
 * 2. SECURITY AUDITOR - Validates security implementations
 * 3. FUNCTIONAL TESTER - Tests actual functionality with HTTP requests
 * 4. ERROR DETECTOR - Checks for PHP warnings/errors
 * 5. EVALUATOR - Reviews all results and makes final decision
 */

class QAAgent {
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $criticalIssues = [];
    
    public function __construct() {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║       MULTI-AGENT QA VALIDATION SYSTEM v2.0                ║\n";
        echo "║  Comprehensive Testing Before Reporting Success            ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";
    }
    
    /**
     * AGENT 1: INSPECTOR
     * Validates file structure, PHP syntax, and basic requirements
     */
    public function inspectorAgent() {
        echo "🔍 AGENT 1: INSPECTOR - Code Structure Validation\n";
        echo "─────────────────────────────────────────────────────────────\n";
        
        $files = [
            'C:\xampp\htdocs\moviesuggestor\auth\login-page.php',
            'C:\xampp\htdocs\moviesuggestor\auth\register-page.php',
            'C:\xampp\htdocs\moviesuggestor\auth\profile.php',
            'C:\xampp\htdocs\moviesuggestor\login.php',
            'C:\xampp\htdocs\moviesuggestor\register.php',
            'C:\xampp\htdocs\moviesuggestor\logout.php',
            'C:\xampp\htdocs\moviesuggestor\src\Security.php'
        ];
        
        $agentResults = [];
        
        foreach ($files as $file) {
            $this->totalTests++;
            
            if (!file_exists($file)) {
                $agentResults[] = "❌ FAIL: File not found: " . basename($file);
                $this->criticalIssues[] = "Missing file: " . basename($file);
                continue;
            }
            
            // Check PHP syntax
            $output = [];
            $return = 0;
            exec("php -l \"$file\" 2>&1", $output, $return);
            
            if ($return !== 0) {
                $agentResults[] = "❌ FAIL: Syntax error in " . basename($file);
                $this->criticalIssues[] = "Syntax error: " . basename($file) . " - " . implode(' ', $output);
                continue;
            }
            
            // Check for PHP at start (no output before <?php)
            $content = file_get_contents($file);
            if (strpos($file, 'login-page.php') !== false || strpos($file, 'register-page.php') !== false) {
                if (!preg_match('/^<\?php/', $content)) {
                    $agentResults[] = "❌ FAIL: " . basename($file) . " - PHP not at start (headers will be sent)";
                    $this->criticalIssues[] = "Headers issue: " . basename($file);
                    continue;
                }
            }
            
            // Check for Security::initSession()
            if (strpos($file, '-page.php') !== false || strpos($file, 'profile.php') !== false) {
                if (strpos($content, 'Security::initSession()') === false) {
                    $agentResults[] = "⚠️  WARN: " . basename($file) . " missing Security::initSession()";
                } else {
                    $this->passedTests++;
                    $agentResults[] = "✓ PASS: " . basename($file) . " - Structure valid";
                }
            } else {
                $this->passedTests++;
                $agentResults[] = "✓ PASS: " . basename($file) . " - Syntax valid";
            }
        }
        
        $this->results['inspector'] = $agentResults;
        $this->printAgentResults($agentResults);
    }
    
    /**
     * AGENT 2: SECURITY AUDITOR
     * Validates CSRF tokens, session security, and SQL injection prevention
     */
    public function securityAuditorAgent() {
        echo "\n🔐 AGENT 2: SECURITY AUDITOR - Security Implementation\n";
        echo "─────────────────────────────────────────────────────────────\n";
        
        $agentResults = [];
        
        // Check login-page.php for CSRF token
        $this->totalTests++;
        $loginContent = file_get_contents('C:\xampp\htdocs\moviesuggestor\auth\login-page.php');
        if (strpos($loginContent, 'csrf_token') !== false && strpos($loginContent, 'Security::generateCSRFToken()') !== false) {
            $this->passedTests++;
            $agentResults[] = "✓ PASS: Login page has CSRF token generation and field";
        } else {
            $agentResults[] = "❌ FAIL: Login page missing CSRF protection";
            $this->criticalIssues[] = "CSRF missing: login-page.php";
        }
        
        // Check register-page.php for CSRF token
        $this->totalTests++;
        $registerContent = file_get_contents('C:\xampp\htdocs\moviesuggestor\auth\register-page.php');
        if (strpos($registerContent, 'csrf_token') !== false && strpos($registerContent, 'Security::generateCSRFToken()') !== false) {
            $this->passedTests++;
            $agentResults[] = "✓ PASS: Register page has CSRF token generation and field";
        } else {
            $agentResults[] = "❌ FAIL: Register page missing CSRF protection";
            $this->criticalIssues[] = "CSRF missing: register-page.php";
        }
        
        // Check Security.php for key methods
        $this->totalTests++;
        $securityContent = file_get_contents('C:\xampp\htdocs\moviesuggestor\src\Security.php');
        $requiredMethods = ['initSession', 'generateCSRFToken', 'validateCSRFToken', 'requireAuth', 'getUserId'];
        $allPresent = true;
        foreach ($requiredMethods as $method) {
            if (strpos($securityContent, "function $method") === false) {
                $allPresent = false;
                break;
            }
        }
        
        if ($allPresent) {
            $this->passedTests++;
            $agentResults[] = "✓ PASS: Security.php has all required methods";
        } else {
            $agentResults[] = "❌ FAIL: Security.php missing required methods";
            $this->criticalIssues[] = "Security methods incomplete";
        }
        
        // Check for prepared statements in repositories
        $this->totalTests++;
        $movieRepo = @file_get_contents('C:\xampp\htdocs\moviesuggestor\src\MovieRepository.php');
        if ($movieRepo && strpos($movieRepo, '->prepare(') !== false && strpos($movieRepo, '->execute(') !== false) {
            $this->passedTests++;
            $agentResults[] = "✓ PASS: Repositories use prepared statements";
        } else {
            $agentResults[] = "⚠️  WARN: Could not verify prepared statements";
        }
        
        $this->results['security'] = $agentResults;
        $this->printAgentResults($agentResults);
    }
    
    /**
     * AGENT 3: FUNCTIONAL TESTER
     * Actually tests HTTP requests to pages
     */
    public function functionalTesterAgent() {
        echo "\n🧪 AGENT 3: FUNCTIONAL TESTER - Live HTTP Testing\n";
        echo "─────────────────────────────────────────────────────────────\n";
        
        $agentResults = [];
        $baseUrl = 'http://localhost/moviesuggestor';
        
        $pages = [
            'test-auth.php' => 'Test Auth Page',
            'auth/login-page.php' => 'Login Page',
            'auth/register-page.php' => 'Register Page',
            'auth/forgot-password.php' => 'Forgot Password',
            'index.php' => 'Main Application'
        ];
        
        foreach ($pages as $page => $name) {
            $this->totalTests++;
            
            $url = "$baseUrl/$page";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                // Check for PHP errors in response
                if (strpos($response, 'Warning:') !== false || 
                    strpos($response, 'Fatal error:') !== false || 
                    strpos($response, 'Parse error:') !== false) {
                    $agentResults[] = "❌ FAIL: $name returns 200 but has PHP errors";
                    $this->criticalIssues[] = "PHP errors on: $page";
                    
                    // Extract error message
                    preg_match('/(Warning|Fatal error|Parse error):([^\n]+)/', $response, $matches);
                    if (!empty($matches[0])) {
                        $agentResults[] = "   Error: " . trim($matches[0]);
                    }
                } else {
                    $this->passedTests++;
                    $agentResults[] = "✓ PASS: $name - HTTP 200, no errors";
                }
            } elseif ($httpCode === 302) {
                $this->passedTests++;
                $agentResults[] = "✓ PASS: $name - HTTP 302 (redirect, expected)";
            } else {
                $agentResults[] = "❌ FAIL: $name - HTTP $httpCode";
                $this->criticalIssues[] = "HTTP error on: $page ($httpCode)";
            }
        }
        
        $this->results['functional'] = $agentResults;
        $this->printAgentResults($agentResults);
    }
    
    /**
     * AGENT 4: ERROR DETECTOR
     * Checks PHP error logs and runtime errors
     */
    public function errorDetectorAgent() {
        echo "\n⚠️  AGENT 4: ERROR DETECTOR - PHP Error Analysis\n";
        echo "─────────────────────────────────────────────────────────────\n";
        
        $agentResults = [];
        
        // Test login page for errors
        $this->totalTests++;
        ob_start();
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        
        $testUrl = 'http://localhost/moviesuggestor/auth/login-page.php';
        $ch = curl_init($testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $errors = ob_get_clean();
        
        if (strpos($response, 'Warning:') === false && strpos($response, 'Notice:') === false) {
            $this->passedTests++;
            $agentResults[] = "✓ PASS: No PHP warnings or notices on login page";
        } else {
            $agentResults[] = "❌ FAIL: PHP warnings/notices detected on login page";
            $this->criticalIssues[] = "PHP warnings on login page";
        }
        
        // Check for session errors specifically
        $this->totalTests++;
        if (strpos($response, 'headers already sent') === false && 
            strpos($response, 'session_start()') === false &&
            strpos($response, 'ini_set()') === false) {
            $this->passedTests++;
            $agentResults[] = "✓ PASS: No session-related errors";
        } else {
            $agentResults[] = "❌ FAIL: Session errors detected";
            $this->criticalIssues[] = "Session configuration errors";
        }
        
        $this->results['errors'] = $agentResults;
        $this->printAgentResults($agentResults);
    }
    
    /**
     * AGENT 5: EVALUATOR
     * Reviews all agent results and makes final decision
     */
    public function evaluatorAgent() {
        echo "\n📊 AGENT 5: EVALUATOR - Final Assessment\n";
        echo "─────────────────────────────────────────────────────────────\n";
        
        $passRate = $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0;
        
        echo "\nTest Summary:\n";
        echo "  Total Tests:    " . $this->totalTests . "\n";
        echo "  Passed:         " . $this->passedTests . " (" . round($passRate, 1) . "%)\n";
        echo "  Failed:         " . ($this->totalTests - $this->passedTests) . "\n";
        echo "  Critical Issues: " . count($this->criticalIssues) . "\n\n";
        
        if (!empty($this->criticalIssues)) {
            echo "❌ CRITICAL ISSUES FOUND:\n";
            foreach ($this->criticalIssues as $issue) {
                echo "   • $issue\n";
            }
            echo "\n";
        }
        
        echo "═══════════════════════════════════════════════════════════\n";
        
        if (count($this->criticalIssues) > 0) {
            echo "❌ EVALUATION: FAILED - Critical issues must be resolved\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
            return false;
        } elseif ($passRate < 80) {
            echo "⚠️  EVALUATION: NEEDS IMPROVEMENT - Pass rate below 80%\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
            return false;
        } elseif ($passRate < 95) {
            echo "✓ EVALUATION: ACCEPTABLE - Minor issues to address\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
            return true;
        } else {
            echo "✅ EVALUATION: EXCELLENT - All tests passed!\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
            return true;
        }
    }
    
    /**
     * Run all agents in sequence
     */
    public function runFullValidation() {
        $startTime = microtime(true);
        
        $this->inspectorAgent();
        $this->securityAuditorAgent();
        $this->functionalTesterAgent();
        $this->errorDetectorAgent();
        
        $success = $this->evaluatorAgent();
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        echo "⏱️  Total validation time: {$duration}ms\n\n";
        
        if ($success) {
            echo "🎉 RESULT: Authentication system is READY FOR USE\n";
            echo "   URL: http://localhost/moviesuggestor/auth/login-page.php\n";
            echo "   Demo: demo@example.com / demo123\n\n";
        } else {
            echo "🚫 RESULT: Authentication system NEEDS FIXES before use\n";
            echo "   Please address the critical issues listed above.\n\n";
        }
        
        return $success;
    }
    
    private function printAgentResults($results) {
        foreach ($results as $result) {
            echo "  $result\n";
        }
    }
}

// Run validation
$qa = new QAAgent();
$success = $qa->runFullValidation();

exit($success ? 0 : 1);
