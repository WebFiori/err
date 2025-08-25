<?php

namespace WebFiori\Tests\Error;

use PHPUnit\Framework\TestCase;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Handler;
use Exception;

/**
 * Tests for infinite loop protection in error handling.
 * 
 * @author Ibrahim
 */
class InfiniteLoopProtectionTest extends TestCase {
    
    protected function setUp(): void {
        Handler::reset();
    }
    
    /**
     * Test that handlers are protected from infinite loops.
     * 
     * @test
     */
    public function testInfiniteLoopProtection(): void {
        $executionCount = 0;
        
        $loopingHandler = new class($executionCount) extends AbstractHandler {
            private $executionCount;
            
            public function __construct(&$executionCount) {
                parent::__construct();
                $this->setName('LoopingHandler');
                $this->executionCount = &$executionCount;
            }
            
            public function handle(): void {
                $this->executionCount++;
                
                // This handler throws an exception, which would normally trigger itself again
                throw new Exception('Handler throws exception');
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($loopingHandler);
        
        // Capture error log output
        $originalErrorLog = ini_get('error_log');
        $tempLogFile = tempnam(sys_get_temp_dir(), 'test_error_log');
        ini_set('error_log', $tempLogFile);
        
        // Trigger the handler
        ob_start();
        Handler::get()->invokeExceptionsHandler(new Exception('Test exception'));
        ob_end_clean();
        
        // Restore original error log setting
        ini_set('error_log', $originalErrorLog);
        
        // Read the logged errors
        $errorLog = file_get_contents($tempLogFile);
        unlink($tempLogFile);
        
        // The handler should only execute once due to infinite loop protection
        $this->assertEquals(1, $executionCount);
        
        // Should log the handler failure
        $this->assertStringContainsString('Handler "LoopingHandler" failed', $errorLog);
    }
    
    /**
     * Test execution count limits.
     * 
     * @test
     */
    public function testExecutionCountLimit(): void {
        $executionCount = 0;
        
        $countingHandler = new class($executionCount) extends AbstractHandler {
            private $executionCount;
            
            public function __construct(&$executionCount) {
                parent::__construct();
                $this->setName('CountingHandler');
                $this->executionCount = &$executionCount;
            }
            
            public function handle(): void {
                $this->executionCount++;
                // This handler executes normally
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($countingHandler);
        
        // Execute the handler multiple times
        for ($i = 0; $i < 5; $i++) {
            Handler::get()->invokeExceptionsHandler(new Exception('Test exception ' . $i));
        }
        
        // Should only execute up to the limit (default is 3)
        $this->assertEquals(3, $executionCount);
        $this->assertEquals(3, Handler::getHandlerExecutionCount('CountingHandler'));
    }
    
    /**
     * Test resetting execution counts.
     * 
     * @test
     */
    public function testResetExecutionCounts(): void {
        $executionCount = 0;
        
        $testHandler = new class($executionCount) extends AbstractHandler {
            private $executionCount;
            
            public function __construct(&$executionCount) {
                parent::__construct();
                $this->setName('TestHandler');
                $this->executionCount = &$executionCount;
            }
            
            public function handle(): void {
                $this->executionCount++;
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($testHandler);
        
        // Execute up to limit
        for ($i = 0; $i < 3; $i++) {
            Handler::get()->invokeExceptionsHandler(new Exception('Test exception ' . $i));
        }
        
        $this->assertEquals(3, $executionCount);
        $this->assertEquals(3, Handler::getHandlerExecutionCount('TestHandler'));
        
        // Reset counts
        Handler::resetExecutionCounts();
        $this->assertEquals(0, Handler::getHandlerExecutionCount('TestHandler'));
        
        // Should be able to execute again
        Handler::get()->invokeExceptionsHandler(new Exception('After reset'));
        $this->assertEquals(4, $executionCount);
        $this->assertEquals(1, Handler::getHandlerExecutionCount('TestHandler'));
    }
    
    /**
     * Test configuring maximum executions.
     * 
     * @test
     */
    public function testConfigureMaxExecutions(): void {
        $executionCount = 0;
        
        $testHandler = new class($executionCount) extends AbstractHandler {
            private $executionCount;
            
            public function __construct(&$executionCount) {
                parent::__construct();
                $this->setName('ConfigTestHandler');
                $this->executionCount = &$executionCount;
            }
            
            public function handle(): void {
                $this->executionCount++;
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($testHandler);
        
        // Set custom limit
        Handler::setMaxHandlerExecutions(5);
        
        // Execute multiple times
        for ($i = 0; $i < 7; $i++) {
            Handler::get()->invokeExceptionsHandler(new Exception('Test exception ' . $i));
        }
        
        // Should execute up to the new limit (5)
        $this->assertEquals(5, $executionCount);
        $this->assertEquals(5, Handler::getHandlerExecutionCount('ConfigTestHandler'));
    }
    
    /**
     * Test recursive handler protection.
     * 
     * @test
     */
    public function testRecursiveHandlerProtection(): void {
        $executionCount = 0;
        
        $recursiveHandler = new class($executionCount) extends AbstractHandler {
            private $executionCount;
            
            public function __construct(&$executionCount) {
                parent::__construct();
                $this->setName('RecursiveHandler');
                $this->executionCount = &$executionCount;
            }
            
            public function handle(): void {
                $this->executionCount++;
                
                // Try to trigger another exception while handling
                Handler::get()->invokeExceptionsHandler(new Exception('Recursive exception'));
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($recursiveHandler);
        
        // Capture error log output
        $originalErrorLog = ini_get('error_log');
        $tempLogFile = tempnam(sys_get_temp_dir(), 'test_error_log');
        ini_set('error_log', $tempLogFile);
        
        // Trigger the handler
        ob_start();
        Handler::get()->invokeExceptionsHandler(new Exception('Initial exception'));
        ob_end_clean();
        
        // Restore original error log setting
        ini_set('error_log', $originalErrorLog);
        
        // Read the logged errors
        $errorLog = file_get_contents($tempLogFile);
        unlink($tempLogFile);
        
        // Should only execute once due to recursive protection
        $this->assertEquals(1, $executionCount);
        
        // Should log the blocked execution
        $this->assertStringContainsString('Already handling an exception', $errorLog);
    }
}
