<?php

namespace WebFiori\Tests\Error;

use PHPUnit\Framework\TestCase;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Handler;
use WebFiori\Error\ErrorHandlerException;
use Exception;
use Throwable;

/**
 * Comprehensive tests for error handling edge cases and scenarios.
 * 
 * @author Ibrahim
 */
class ErrorHandlingTest extends TestCase {
    use OutputBufferingTrait;
    
    protected function setUp(): void {
        Handler::reset();
    }
    
    protected function tearDown(): void {
        $this->cleanupOutputBuffers();
        Handler::reset();
    }
    
    /**
     * Test handler that throws an exception during execution.
     * 
     * @test
     */
    public function testHandlerThatThrowsException(): void {
        $failingHandler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('FailingHandler');
            }
            
            public function handle(): void {
                throw new Exception('Handler failed');
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($failingHandler);
        
        // Capture the error log by temporarily redirecting it
        $originalErrorLog = ini_get('error_log');
        $tempLogFile = tempnam(sys_get_temp_dir(), 'test_error_log');
        ini_set('error_log', $tempLogFile);
        
        // This should not throw an exception, but log the handler failure
        $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Test exception'));
        });
        
        // Restore original error log setting
        ini_set('error_log', $originalErrorLog);
        
        // Read the logged error
        $errorLog = file_get_contents($tempLogFile);
        unlink($tempLogFile);
        
        // Verify the handler failure was logged
        $this->assertStringContainsString('Handler "FailingHandler" failed', $errorLog);
    }
    
    /**
     * Test multiple handlers with different priorities.
     * 
     * @test
     */
    public function testHandlerPriorityExecution(): void {
        $executionOrder = [];
        
        $handler1 = new class($executionOrder) extends AbstractHandler {
            private $executionOrder;
            
            public function __construct(&$executionOrder) {
                parent::__construct();
                $this->setName('Handler1');
                $this->setPriority(1);
                $this->executionOrder = &$executionOrder;
            }
            
            public function handle(): void {
                $this->executionOrder[] = 'Handler1';
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        $handler2 = new class($executionOrder) extends AbstractHandler {
            private $executionOrder;
            
            public function __construct(&$executionOrder) {
                parent::__construct();
                $this->setName('Handler2');
                $this->setPriority(10);
                $this->executionOrder = &$executionOrder;
            }
            
            public function handle(): void {
                $this->executionOrder[] = 'Handler2';
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($handler1);
        Handler::registerHandler($handler2);
        
        $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Test'));
        });
        
        // Handler2 should execute first due to higher priority
        $this->assertEquals(['Handler2', 'Handler1'], $executionOrder);
    }
    
    /**
     * Test handler state management.
     * 
     * @test
     */
    public function testHandlerStateManagement(): void {
        $handler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('StateTestHandler');
            }
            
            public function handle(): void {
                // Handler execution
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($handler);
        
        // Initially not executed
        $this->assertFalse($handler->isExecuted());
        $this->assertFalse($handler->isExecuting());
        
        ob_start();
        Handler::get()->invokeExceptionsHandler(new Exception('Test'));
        ob_end_clean();
        
        // After execution
        $this->assertTrue($handler->isExecuted());
        $this->assertFalse($handler->isExecuting());
    }
    
    /**
     * Test shutdown handler execution.
     * 
     * @test
     */
    public function testShutdownHandlerExecution(): void {
        $executed = false;
        
        $shutdownHandler = new class($executed) extends AbstractHandler {
            private $executed;
            
            public function __construct(&$executed) {
                parent::__construct();
                $this->setName('ShutdownHandler');
                $this->executed = &$executed;
            }
            
            public function handle(): void {
                $this->executed = true;
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return true;
            }
        };
        
        Handler::registerHandler($shutdownHandler);
        
        // Normal exception handling should not execute shutdown handler
        ob_start();
        Handler::get()->invokeExceptionsHandler(new Exception('Test'));
        ob_end_clean();
        
        $this->assertFalse($executed);
        
        // Shutdown handler should execute during shutdown
        Handler::get()->invokeShutdownHandler();
        
        $this->assertTrue($executed);
    }
    
    /**
     * Test error to exception conversion.
     * 
     * @test
     */
    public function testErrorToExceptionConversion(): void {
        $this->expectException(ErrorHandlerException::class);
        $this->expectExceptionMessageMatches('/An exception caused by an error/');
        
        // Trigger an undefined variable error
        $undefinedVariable = $nonExistentVariable;
    }
    
    /**
     * Test handler registration with duplicate names.
     * 
     * @test
     */
    public function testDuplicateHandlerRegistration(): void {
        $handler1 = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('DuplicateHandler');
            }
            
            public function handle(): void {}
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }
        };
        
        $handler2 = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('DuplicateHandler');
            }
            
            public function handle(): void {}
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }
        };
        
        Handler::registerHandler($handler1);
        $initialCount = count(Handler::getHandlers());
        
        // Registering handler with same name should not add it
        Handler::registerHandler($handler2);
        $finalCount = count(Handler::getHandlers());
        
        $this->assertEquals($initialCount, $finalCount);
    }
    
    /**
     * Test inactive handler is not executed.
     * 
     * @test
     */
    public function testInactiveHandlerNotExecuted(): void {
        $executed = false;
        
        $inactiveHandler = new class($executed) extends AbstractHandler {
            private $executed;
            
            public function __construct(&$executed) {
                parent::__construct();
                $this->setName('InactiveHandler');
                $this->executed = &$executed;
            }
            
            public function handle(): void {
                $this->executed = true;
            }
            
            public function isActive(): bool {
                return false; // Inactive
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($inactiveHandler);
        
        ob_start();
        Handler::get()->invokeExceptionsHandler(new Exception('Test'));
        ob_end_clean();
        
        $this->assertFalse($executed);
    }
}
