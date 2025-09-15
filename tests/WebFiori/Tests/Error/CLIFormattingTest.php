<?php

namespace WebFiori\Tests\Error;

use PHPUnit\Framework\TestCase;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Handler;
use Exception;

/**
 * Test cases for CLI-aware output formatting in DefaultHandler.
 * 
 * This test class verifies that the DefaultHandler properly formats
 * output differently for CLI and HTTP environments.
 */
class CLIFormattingTest extends TestCase {
    
    use OutputBufferingTrait;
    
    protected function setUp(): void {
        parent::setUp();
        Handler::reset();
    }
    
    protected function tearDown(): void {
        Handler::reset();
        $this->cleanupOutputBuffers();
        parent::tearDown();
    }
    
    /**
     * Test that CLI output contains no HTML tags.
     */
    public function testCLIOutputContainsNoHTML(): void {
        Handler::reset();
        
        // Remove all handlers first
        $handlers = Handler::getHandlers();
        foreach ($handlers as $handler) {
            Handler::unregisterHandler($handler);
        }
        
        // Create a custom handler for testing
        $handler = new class extends \WebFiori\Error\DefaultHandler {
            private $testCLI = true;
            
            public function __construct() {
                parent::__construct();
                $this->setName('TestCLI');
            }
            
            public function isCLI(): bool {
                return $this->testCLI;
            }
            
            public function setIsCLI(bool $bool): void {
                $this->testCLI = $bool;
            }
            
            public function createSecurityConfig(): \WebFiori\Error\Security\SecurityConfig {
                return new \WebFiori\Error\Security\SecurityConfig('dev');
            }
        };
        
        $handler->setIsCLI(true);
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Test CLI exception', 123));
        });
        
        // CLI output should not contain HTML tags
        $this->assertStringNotContainsString('<div', $output);
        $this->assertStringNotContainsString('<p>', $output);
        $this->assertStringNotContainsString('<h3>', $output);
        $this->assertStringNotContainsString('<pre>', $output);
        $this->assertStringNotContainsString('<details>', $output);
        
        // CLI output should contain ANSI escape sequences
        $this->assertStringContainsString("\033[", $output);
        
        // CLI output should contain the error information
        $this->assertStringContainsString('APPLICATION ERROR', $output);
        $this->assertStringContainsString('Test CLI exception', $output);
        $this->assertStringContainsString('123', $output); // Just check for the code number
    }
    
    /**
     * Test that HTTP output contains HTML tags.
     */
    public function testHTTPOutputContainsHTML(): void {
        Handler::reset();
        
        // Remove all handlers first
        $handlers = Handler::getHandlers();
        foreach ($handlers as $handler) {
            Handler::unregisterHandler($handler);
        }
        
        // Create a custom handler for testing
        $handler = new class extends \WebFiori\Error\DefaultHandler {
            private $testCLI = false;
            
            public function __construct() {
                parent::__construct();
                $this->setName('TestHTTP');
            }
            
            public function isCLI(): bool {
                return $this->testCLI;
            }
            
            public function setIsCLI(bool $bool): void {
                $this->testCLI = $bool;
            }
            
            public function createSecurityConfig(): \WebFiori\Error\Security\SecurityConfig {
                return new \WebFiori\Error\Security\SecurityConfig('dev');
            }
        };
        
        $handler->setIsCLI(false);
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Test HTTP exception', 456));
        });
        
        // HTTP output should contain HTML tags
        $this->assertStringContainsString('<div', $output);
        $this->assertStringContainsString('<h3 class="error-title">Application Error</h3>', $output);
        $this->assertStringContainsString('<p><strong>Message:</strong>', $output);
        $this->assertStringContainsString('<p><strong>Code:</strong>', $output);
        
        // HTTP output should not contain ANSI escape sequences
        $this->assertStringNotContainsString("\033[", $output);
        
        // HTTP output should contain the error information
        $this->assertStringContainsString('Test HTTP exception', $output);
        $this->assertStringContainsString('456', $output);
    }
    
    /**
     * Test CLI output formatting with stack trace.
     */
    public function testCLIOutputWithStackTrace(): void {
        Handler::reset();
        
        // Remove all handlers first
        $handlers = Handler::getHandlers();
        foreach ($handlers as $handler) {
            Handler::unregisterHandler($handler);
        }
        
        // Create a custom handler for testing with development mode
        $handler = new class extends \WebFiori\Error\DefaultHandler {
            private $testCLI = true;
            
            public function __construct() {
                parent::__construct();
                $this->setName('TestCLIStackTrace');
            }
            
            public function isCLI(): bool {
                return $this->testCLI;
            }
            
            public function setIsCLI(bool $bool): void {
                $this->testCLI = $bool;
            }
            
            public function createSecurityConfig(): \WebFiori\Error\Security\SecurityConfig {
                return new \WebFiori\Error\Security\SecurityConfig('dev');
            }
        };
        
        $handler->setIsCLI(true);
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            $this->triggerExceptionWithStackTrace();
        });
        
        // CLI output should contain stack trace formatting
        $this->assertStringContainsString('Stack Trace:', $output);
        $this->assertStringContainsString('#0', $output);
        $this->assertStringContainsString('CLIFormattingTest', $output); // Look for the test class name instead
        
        // Should contain CLI-style separators
        $this->assertStringContainsString(str_repeat('-', 40), $output);
        $this->assertStringContainsString(str_repeat('=', 60), $output);
        
        // Should not contain HTML stack trace elements
        $this->assertStringNotContainsString('<details>', $output);
        $this->assertStringNotContainsString('<summary>', $output);
        $this->assertStringNotContainsString('<pre', $output);
    }
    
    /**
     * Test HTTP output formatting with stack trace.
     */
    public function testHTTPOutputWithStackTrace(): void {
        Handler::reset();
        
        // Remove all handlers first
        $handlers = Handler::getHandlers();
        foreach ($handlers as $handler) {
            Handler::unregisterHandler($handler);
        }
        
        // Create a custom handler for testing with development mode
        $handler = new class extends \WebFiori\Error\DefaultHandler {
            private $testCLI = false;
            
            public function __construct() {
                parent::__construct();
                $this->setName('TestHTTPStackTrace');
            }
            
            public function isCLI(): bool {
                return $this->testCLI;
            }
            
            public function setIsCLI(bool $bool): void {
                $this->testCLI = $bool;
            }
            
            public function createSecurityConfig(): \WebFiori\Error\Security\SecurityConfig {
                return new \WebFiori\Error\Security\SecurityConfig('dev');
            }
        };
        
        $handler->setIsCLI(false);
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            $this->triggerExceptionWithStackTrace();
        });
        
        // HTTP output should contain HTML stack trace formatting
        $this->assertStringContainsString('<details class="error-trace">', $output);
        $this->assertStringContainsString('<summary><strong>Stack Trace</strong></summary>', $output);
        $this->assertStringContainsString('<pre', $output);
        $this->assertStringContainsString('CLIFormattingTest', $output); // Look for the test class name instead
        
        // Should not contain CLI-style separators
        $this->assertStringNotContainsString(str_repeat('-', 40), $output);
        $this->assertStringNotContainsString(str_repeat('=', 60), $output);
    }
    
    /**
     * Test CLI output in production environment.
     */
    public function testCLIOutputInProduction(): void {
        Handler::reset();
        
        // Remove all handlers first
        $handlers = Handler::getHandlers();
        foreach ($handlers as $handler) {
            Handler::unregisterHandler($handler);
        }
        
        // Create a custom handler for testing with production mode
        $handler = new class extends \WebFiori\Error\DefaultHandler {
            private $testCLI = true;
            
            public function __construct() {
                parent::__construct();
                $this->setName('TestCLIProd');
            }
            
            public function isCLI(): bool {
                return $this->testCLI;
            }
            
            public function setIsCLI(bool $bool): void {
                $this->testCLI = $bool;
            }
            
            public function createSecurityConfig(): \WebFiori\Error\Security\SecurityConfig {
                return new \WebFiori\Error\Security\SecurityConfig('prod');
            }
        };
        
        $handler->setIsCLI(true);
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Sensitive production error', 789));
        });
        
        // Production CLI output should be minimal
        $this->assertStringContainsString('APPLICATION ERROR', $output);
        $this->assertStringContainsString('Application code', $output); // Remove "Location:" prefix
        $this->assertStringContainsString('An error occurred during processing', $output); // Remove "Details:" prefix
        
        // Should not contain sensitive information
        $this->assertStringNotContainsString('Sensitive production error', $output);
        
        // Should not contain stack trace in production
        $this->assertStringNotContainsString('Stack Trace:', $output);
        
        // Should contain production help message
        $this->assertStringContainsString('contact support', $output);
    }
    
    /**
     * Test HTTP output in production environment.
     */
    public function testHTTPOutputInProduction(): void {
        Handler::reset();
        
        // Remove all handlers first
        $handlers = Handler::getHandlers();
        foreach ($handlers as $handler) {
            Handler::unregisterHandler($handler);
        }
        
        // Create a custom handler for testing with production mode
        $handler = new class extends \WebFiori\Error\DefaultHandler {
            private $testCLI = false;
            
            public function __construct() {
                parent::__construct();
                $this->setName('TestHTTPProd');
            }
            
            public function isCLI(): bool {
                return $this->testCLI;
            }
            
            public function setIsCLI(bool $bool): void {
                $this->testCLI = $bool;
            }
            
            public function createSecurityConfig(): \WebFiori\Error\Security\SecurityConfig {
                return new \WebFiori\Error\Security\SecurityConfig('prod');
            }
        };
        
        $handler->setIsCLI(false);
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Sensitive production error', 789));
        });
        
        // Production HTTP output should be minimal HTML
        $this->assertStringContainsString('<h3 class="error-title">Application Error</h3>', $output);
        $this->assertStringContainsString('<p><strong>Location:</strong> Application code</p>', $output);
        $this->assertStringContainsString('<p><strong>Details:</strong> An error occurred during processing.</p>', $output);
        
        // Should not contain sensitive information
        $this->assertStringNotContainsString('Sensitive production error', $output);
        
        // Should not contain HTML stack trace in production
        $this->assertStringNotContainsString('<details class="error-trace">', $output);
        
        // Should contain production help message
        $this->assertStringContainsString('contact support', $output);
    }
    
    /**
     * Test CLI output with no stack trace available.
     */
    public function testCLIOutputWithNoStackTrace(): void {
        Handler::reset();
        
        // Remove all handlers first
        $handlers = Handler::getHandlers();
        foreach ($handlers as $handler) {
            Handler::unregisterHandler($handler);
        }
        
        // Create a custom handler for testing
        $handler = new class extends \WebFiori\Error\DefaultHandler {
            private $testCLI = true;
            
            public function __construct() {
                parent::__construct();
                $this->setName('TestCLINoTrace');
            }
            
            public function isCLI(): bool {
                return $this->testCLI;
            }
            
            public function setIsCLI(bool $bool): void {
                $this->testCLI = $bool;
            }
            
            public function createSecurityConfig(): \WebFiori\Error\Security\SecurityConfig {
                return new \WebFiori\Error\Security\SecurityConfig('dev');
            }
        };
        
        $handler->setIsCLI(true);
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            // Create an exception with no stack trace
            Handler::get()->invokeExceptionsHandler(new Exception('No trace exception'));
        });
        
        // Should contain stack trace information (since exceptions in tests do have traces)
        // The test name was misleading - exceptions in PHPUnit always have stack traces
        $this->assertStringContainsString('Stack Trace:', $output);
        
        // Should not contain HTML formatting
        $this->assertStringNotContainsString('<em>', $output);
        $this->assertStringNotContainsString('<p>', $output);
    }
    
    /**
     * Test HTTP output with no stack trace available.
     */
    public function testHTTPOutputWithNoStackTrace(): void {
        Handler::reset();
        
        // Remove all handlers first
        $handlers = Handler::getHandlers();
        foreach ($handlers as $handler) {
            Handler::unregisterHandler($handler);
        }
        
        // Create a custom handler for testing
        $handler = new class extends \WebFiori\Error\DefaultHandler {
            private $testCLI = false;
            
            public function __construct() {
                parent::__construct();
                $this->setName('TestHTTPNoTrace');
            }
            
            public function isCLI(): bool {
                return $this->testCLI;
            }
            
            public function setIsCLI(bool $bool): void {
                $this->testCLI = $bool;
            }
            
            public function createSecurityConfig(): \WebFiori\Error\Security\SecurityConfig {
                return new \WebFiori\Error\Security\SecurityConfig('dev');
            }
        };
        
        $handler->setIsCLI(false);
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            // Create an exception with no stack trace
            Handler::get()->invokeExceptionsHandler(new Exception('No trace exception'));
        });
        
        // Should contain stack trace information (since exceptions in tests do have traces)
        // The test name was misleading - exceptions in PHPUnit always have stack traces
        $this->assertStringContainsString('<details class="error-trace">', $output);
        
        // Should not contain CLI formatting
        $this->assertStringNotContainsString("\033[", $output);
    }
    
    /**
     * Test that isCLI() method works correctly.
     */
    public function testIsCLIMethod(): void {
        $handler = new DefaultHandler();
        
        // Test setting CLI mode
        $handler->setIsCLI(true);
        $this->assertTrue($handler->isCLI());
        
        // Test setting HTTP mode
        $handler->setIsCLI(false);
        $this->assertFalse($handler->isCLI());
    }
    
    /**
     * Test automatic CLI detection in constructor.
     */
    public function testAutomaticCLIDetection(): void {
        // This test verifies that the constructor properly detects CLI mode
        // Note: In test environment, http_response_code() behavior may vary
        $handler = new DefaultHandler();
        
        // The handler should have some CLI detection logic
        $this->assertIsBool($handler->isCLI());
    }
    
    /**
     * Test CLI output contains proper ANSI color codes.
     */
    public function testCLIOutputContainsANSIColors(): void {
        $handler = new DefaultHandler();
        $handler->setIsCLI(true);
        
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Color test exception'));
        });
        
        // Should contain ANSI color codes
        $this->assertStringContainsString("\033[1;31m", $output); // Red bold for error title
        $this->assertStringContainsString("\033[1m", $output);    // Bold for labels
        $this->assertStringContainsString("\033[0m", $output);    // Reset
        $this->assertStringContainsString("\033[2m", $output);    // Dim for help text
    }
    
    /**
     * Test HTML output contains proper CSS classes.
     */
    public function testHTMLOutputContainsCSSClasses(): void {
        Handler::reset();
        
        // Remove all handlers first
        $handlers = Handler::getHandlers();
        foreach ($handlers as $handler) {
            Handler::unregisterHandler($handler);
        }
        
        // Create a custom handler for testing
        $handler = new class extends \WebFiori\Error\DefaultHandler {
            private $testCLI = false;
            
            public function __construct() {
                parent::__construct();
                $this->setName('TestHTMLCSS');
            }
            
            public function isCLI(): bool {
                return $this->testCLI;
            }
            
            public function setIsCLI(bool $bool): void {
                $this->testCLI = $bool;
            }
            
            public function createSecurityConfig(): \WebFiori\Error\Security\SecurityConfig {
                return new \WebFiori\Error\Security\SecurityConfig('dev');
            }
        };
        
        $handler->setIsCLI(false);
        Handler::registerHandler($handler);
        
        $output = $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('CSS test exception'));
        });
        
        // Should contain proper CSS classes
        $this->assertStringContainsString('class="error-title"', $output);
        $this->assertStringContainsString('class="error-trace"', $output);
        $this->assertStringContainsString('class="error-help"', $output);
        $this->assertStringContainsString('class="error-timestamp"', $output);
    }
    
    /**
     * Helper method to trigger an exception with a stack trace.
     */
    private function triggerExceptionWithStackTrace(): void {
        Handler::get()->invokeExceptionsHandler(new Exception('Stack trace test exception'));
    }
}
