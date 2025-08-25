<?php

namespace WebFiori\Tests\Error;

use PHPUnit\Framework\TestCase;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Handler;
use WebFiori\Error\Security\SecurityConfig;
use WebFiori\Error\Security\PathSanitizer;
use WebFiori\Error\Security\OutputSanitizer;
use WebFiori\Error\Security\StackTraceFilter;
use Exception;

/**
 * Tests for security features in the error handling system.
 * 
 * @author Ibrahim
 */
class SecurityTest extends TestCase {
    
    protected function setUp(): void {
        Handler::reset();
    }
    
    /**
     * Test SecurityConfig environment detection.
     * 
     * @test
     */
    public function testSecurityConfigEnvironmentDetection(): void {
        // Test development environment (default)
        $config = new SecurityConfig();
        $this->assertTrue($config->isDevelopment());
        $this->assertFalse($config->isProduction());
        $this->assertTrue($config->shouldShowFullPaths());
        $this->assertTrue($config->shouldShowStackTrace());
    }
    
    /**
     * Test SecurityConfig with explicit production environment.
     * 
     * @test
     */
    public function testSecurityConfigProductionEnvironment(): void {
        $config = new SecurityConfig(SecurityConfig::LEVEL_PROD);
        $this->assertTrue($config->isProduction());
        $this->assertFalse($config->isDevelopment());
        $this->assertFalse($config->shouldShowFullPaths());
        $this->assertFalse($config->shouldShowStackTrace());
        $this->assertFalse($config->allowRawExceptionAccess());
    }
    
    /**
     * Test PathSanitizer in different environments.
     * 
     * @test
     */
    public function testPathSanitizerBehavior(): void {
        // Development environment - show full paths
        $devConfig = new SecurityConfig(SecurityConfig::LEVEL_DEV);
        $devSanitizer = new PathSanitizer($devConfig);
        
        $fullPath = '/var/www/html/myapp/src/Controller/UserController.php';
        $this->assertEquals($fullPath, $devSanitizer->sanitizePath($fullPath));
        
        // Production environment - show only filename
        $prodConfig = new SecurityConfig(SecurityConfig::LEVEL_PROD);
        $prodSanitizer = new PathSanitizer($prodConfig);
        
        $this->assertEquals('UserController.php', $prodSanitizer->sanitizePath($fullPath));
    }
    
    /**
     * Test OutputSanitizer message filtering.
     * 
     * @test
     */
    public function testOutputSanitizerMessageFiltering(): void {
        $prodConfig = new SecurityConfig(SecurityConfig::LEVEL_PROD);
        $sanitizer = new OutputSanitizer($prodConfig);
        
        // Test password filtering
        $messageWithPassword = 'Database connection failed: password=secret123 host=localhost';
        $sanitized = $sanitizer->sanitizeMessage($messageWithPassword);
        $this->assertStringNotContainsString('secret123', $sanitized);
        $this->assertStringContainsString('[REDACTED]', $sanitized);
        
        // Test token filtering
        $messageWithToken = 'API call failed with token: abc123xyz';
        $sanitized = $sanitizer->sanitizeMessage($messageWithToken);
        $this->assertStringNotContainsString('abc123xyz', $sanitized);
    }
    
    /**
     * Test OutputSanitizer context sanitization.
     * 
     * @test
     */
    public function testOutputSanitizerContextSanitization(): void {
        $config = new SecurityConfig(SecurityConfig::LEVEL_PROD);
        $sanitizer = new OutputSanitizer($config);
        
        $context = [
            'user_id' => 123,
            'password' => 'secret123',
            'api_key' => 'key_abc123',
            'normal_data' => 'safe_value'
        ];
        
        $sanitized = $sanitizer->sanitizeContext($context);
        
        $this->assertEquals(123, $sanitized['user_id']);
        $this->assertEquals('safe_value', $sanitized['normal_data']);
        
        // Check that sensitive keys are sanitized
        $keys = array_keys($sanitized);
        $this->assertContains('[SENSITIVE_KEY]', $keys); // password key should be sanitized
        $this->assertContains('[SENSITIVE_KEY]', $keys); // api_key key should be sanitized
    }
    
    /**
     * Test secure custom handler behavior.
     * 
     * @test
     */
    public function testSecureCustomHandlerBehavior(): void {
        $outputCaptured = '';
        
        $secureHandler = new class($outputCaptured) extends AbstractHandler {
            private $output;
            
            public function __construct(&$output) {
                parent::__construct();
                $this->setName('SecureTestHandler');
                $this->output = &$output;
            }
            
            public function handle(): void {
                // Capture output instead of echoing
                ob_start();
                $this->secureOutput('<div>Error in: ' . $this->getClass() . '</div>');
                $this->secureOutput('<div>Message: ' . $this->getMessage() . '</div>');
                $this->output = ob_get_contents();
                ob_end_clean();
            }
            
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }
        };
        
        Handler::registerHandler($secureHandler);
        
        // Create an exception with sensitive information
        $exception = new Exception('Database error: password=secret123');
        
        Handler::get()->invokeExceptionsHandler($exception);
        
        // Verify that sensitive information is filtered
        $this->assertStringNotContainsString('secret123', $outputCaptured);
        $this->assertStringContainsString('Error in:', $outputCaptured);
    }
    
    /**
     * Test that getException() returns null in production.
     * 
     * @test
     */
    public function testGetExceptionSecurityInProduction(): void {
        $prodHandler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('ProductionTestHandler');
            }
            
            public function handle(): void {
                // Try to access raw exception in production
                $exception = $this->getException();
                if ($exception === null) {
                    $this->secureOutput('Exception access blocked for security');
                } else {
                    $this->secureOutput('Security breach: raw exception accessible');
                }
            }
            
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }
            
            // Override security config to force production mode
            protected function createSecurityConfig(): SecurityConfig {
                return new SecurityConfig(SecurityConfig::LEVEL_PROD);
            }
        };
        
        Handler::registerHandler($prodHandler);
        
        ob_start();
        Handler::get()->invokeExceptionsHandler(new Exception('Test exception'));
        $output = ob_get_contents();
        ob_end_clean();
        
        $this->assertStringContainsString('Exception access blocked for security', $output);
        $this->assertStringNotContainsString('Security breach', $output);
    }
    
    /**
     * Test stack trace filtering.
     * 
     * @test
     */
    public function testStackTraceFiltering(): void {
        $devConfig = new SecurityConfig(SecurityConfig::LEVEL_DEV);
        
        // Create a custom production config that allows limited stack traces for testing
        $prodConfig = new class extends SecurityConfig {
            public function __construct() {
                parent::__construct(SecurityConfig::LEVEL_PROD);
            }
            
            public function shouldShowStackTrace(): bool {
                return true; // Override for testing
            }
            
            public function getMaxTraceDepth(): int {
                return 10; // Override for testing
            }
        };
        
        $devPathSanitizer = new PathSanitizer($devConfig);
        $prodPathSanitizer = new PathSanitizer($prodConfig);
        $devFilter = new StackTraceFilter($devConfig, $devPathSanitizer);
        $prodFilter = new StackTraceFilter($prodConfig, $prodPathSanitizer);
        
        // Create mock trace entries
        $mockTrace = [
            new \WebFiori\Error\TraceEntry(['file' => '/app/src/Controller.php', 'line' => 10, 'class' => 'Controller']),
            new \WebFiori\Error\TraceEntry(['file' => '/app/vendor/package/File.php', 'line' => 20, 'class' => 'Package']),
        ];
        
        // Development should show both entries
        $devFiltered = $devFilter->filterTrace($mockTrace);
        $this->assertCount(2, $devFiltered);
        
        // Production should filter out vendor files
        $prodFiltered = $prodFilter->filterTrace($mockTrace);
        $this->assertCount(1, $prodFiltered); // Only non-vendor file
    }
    
    /**
     * Test handler failure fallback.
     * 
     * @test
     */
    public function testHandlerFailureFallback(): void {
        $failingHandler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('FailingHandler');
            }
            
            public function handle(): void {
                throw new Exception('Handler implementation failed');
            }
            
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }
        };
        
        Handler::registerHandler($failingHandler);
        
        ob_start();
        Handler::get()->invokeExceptionsHandler(new Exception('Original exception'));
        $output = ob_get_contents();
        ob_end_clean();
        
        // Should show fallback message, not crash
        $this->assertStringContainsString('Original exception', $output);
    }
    
    /**
     * Test sensitive path detection.
     * 
     * @test
     */
    public function testSensitivePathDetection(): void {
        $config = new SecurityConfig();
        $sanitizer = new PathSanitizer($config);
        
        // Test sensitive paths
        $this->assertTrue($sanitizer->isSensitivePath('/app/.env'));
        $this->assertTrue($sanitizer->isSensitivePath('/app/vendor/package/file.php'));
        $this->assertTrue($sanitizer->isSensitivePath('/app/config/database.php'));
        
        // Test non-sensitive paths
        $this->assertFalse($sanitizer->isSensitivePath('/app/src/Controller.php'));
        $this->assertFalse($sanitizer->isSensitivePath('/app/public/index.php'));
    }
    
    /**
     * Test HTML sanitization.
     * 
     * @test
     */
    public function testHtmlSanitization(): void {
        $config = new SecurityConfig();
        $sanitizer = new OutputSanitizer($config);
        
        // Test XSS prevention
        $maliciousContent = '<script>alert("xss")</script><div onclick="evil()">Content</div>';
        $sanitized = $sanitizer->sanitize($maliciousContent);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('onclick=', $sanitized);
        $this->assertStringContainsString('Content', $sanitized);
    }
    
    /**
     * Test CSP compliance.
     * 
     * @test
     */
    public function testCSPCompliance(): void {
        $config = new SecurityConfig(SecurityConfig::LEVEL_PROD); // Production disables inline styles
        $sanitizer = new OutputSanitizer($config);
        
        $contentWithInlineStyles = '<div style="color: red;">Error message</div>';
        $sanitized = $sanitizer->sanitize($contentWithInlineStyles);
        
        // Inline styles should be removed in production
        $this->assertStringNotContainsString('style=', $sanitized);
        $this->assertStringContainsString('class="error-container"', $sanitized);
    }
}
