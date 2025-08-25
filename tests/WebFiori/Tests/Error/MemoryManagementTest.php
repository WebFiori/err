<?php

namespace WebFiori\Tests\Error;

use PHPUnit\Framework\TestCase;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Handler;
use WebFiori\Error\TraceEntry;
use Exception;

/**
 * Tests for memory management and performance optimizations.
 * 
 * @author Ibrahim
 */
class MemoryManagementTest extends TestCase {
    use OutputBufferingTrait;
    
    protected function setUp(): void {
        Handler::reset();
    }
    
    protected function tearDown(): void {
        $this->cleanupOutputBuffers();
        Handler::reset();
    }
    
    /**
     * Test memory cleanup functionality.
     * 
     * @test
     */
    public function testMemoryCleanup(): void {
        $initialMemory = memory_get_usage();
        
        // Create multiple handlers
        for ($i = 0; $i < 10; $i++) {
            $handler = new class($i) extends AbstractHandler {
                private $id;
                
                public function __construct($id) {
                    parent::__construct();
                    $this->id = $id;
                    $this->setName('TestHandler' . $id);
                }
                
                public function handle(): void {
                    // Simulate some work
                }
                
                public function isActive(): bool {
                    return true;
                }
                
                public function isShutdownHandler(): bool {
                    return false;
                }
            };
            
            Handler::registerHandler($handler);
        }
        
        // Trigger some exceptions to create execution counters
        $this->captureOutput(function() {
            for ($i = 0; $i < 5; $i++) {
                Handler::get()->invokeExceptionsHandler(new Exception('Test exception ' . $i));
            }
        });
        
        $beforeCleanup = memory_get_usage();
        $statsBefore = Handler::getMemoryStats();
        
        // Perform cleanup
        Handler::cleanupMemory();
        
        $afterCleanup = memory_get_usage();
        $statsAfter = Handler::getMemoryStats();
        
        // Memory should be managed (allow for some variance)
        $this->assertGreaterThan(0, $statsBefore['handler_count']);
        $this->assertEquals($statsBefore['handler_count'], $statsAfter['handler_count']);
    }
    
    /**
     * Test handler cleanup when removing handlers.
     * 
     * @test
     */
    public function testHandlerCleanup(): void {
        $handler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('CleanupTestHandler');
            }
            
            public function handle(): void {
                // Simulate work
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($handler);
        
        // Trigger exception to set up state
        $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Test exception'));
        });
        
        // Check handler has state
        $memoryUsage = $handler->getMemoryUsage();
        $this->assertEquals('CleanupTestHandler', $memoryUsage['handler_name']);
        $this->assertTrue($memoryUsage['is_executed']);
        
        // Remove handler (should trigger cleanup)
        $removed = Handler::unregisterHandler($handler);
        $this->assertTrue($removed);
        
        // Check execution count was cleaned up
        $this->assertEquals(0, Handler::getHandlerExecutionCount('CleanupTestHandler'));
    }
    
    /**
     * Test memory statistics reporting.
     * 
     * @test
     */
    public function testMemoryStatistics(): void {
        $stats = Handler::getMemoryStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('current_usage', $stats);
        $this->assertArrayHasKey('peak_usage', $stats);
        $this->assertArrayHasKey('handler_count', $stats);
        $this->assertArrayHasKey('execution_counters', $stats);
        $this->assertArrayHasKey('weak_references', $stats);
        $this->assertArrayHasKey('threshold', $stats);
        
        $this->assertIsInt($stats['current_usage']);
        $this->assertIsInt($stats['peak_usage']);
        $this->assertIsInt($stats['handler_count']);
        $this->assertIsInt($stats['execution_counters']);
        $this->assertIsInt($stats['weak_references']);
        $this->assertIsInt($stats['threshold']);
    }
    
    /**
     * Test memory threshold configuration.
     * 
     * @test
     */
    public function testMemoryThreshold(): void {
        $originalStats = Handler::getMemoryStats();
        $originalThreshold = $originalStats['threshold'];
        
        // Set new threshold
        $newThreshold = 100 * 1024 * 1024; // 100MB
        Handler::setMemoryThreshold($newThreshold);
        
        $newStats = Handler::getMemoryStats();
        $this->assertEquals($newThreshold, $newStats['threshold']);
        
        // Test invalid threshold (should be ignored)
        Handler::setMemoryThreshold(-1);
        $invalidStats = Handler::getMemoryStats();
        $this->assertEquals($newThreshold, $invalidStats['threshold']);
    }
    
    /**
     * Test TraceEntry memory optimization.
     * 
     * @test
     */
    public function testTraceEntryMemoryOptimization(): void {
        $entry = new TraceEntry([
            'file' => '/path/to/file.php',
            'line' => 42,
            'class' => 'TestClass',
            'function' => 'testMethod'
        ]);
        
        // First call should compute and cache
        $string1 = (string) $entry;
        $memoryUsage1 = $entry->getMemoryUsage();
        
        // Second call should use cache
        $string2 = (string) $entry;
        $memoryUsage2 = $entry->getMemoryUsage();
        
        $this->assertEquals($string1, $string2);
        $this->assertEquals($memoryUsage1, $memoryUsage2);
        $this->assertGreaterThan(0, $memoryUsage1);
        
        // Clear cache
        $entry->clearCache();
        $memoryUsage3 = $entry->getMemoryUsage();
        $this->assertLessThan($memoryUsage1, $memoryUsage3);
    }
    
    /**
     * Test system shutdown cleanup.
     * 
     * @test
     */
    public function testSystemShutdown(): void {
        // Register some handlers
        for ($i = 0; $i < 3; $i++) {
            $handler = new class($i) extends AbstractHandler {
                private $id;
                
                public function __construct($id) {
                    parent::__construct();
                    $this->id = $id;
                    $this->setName('ShutdownTestHandler' . $id);
                }
                
                public function handle(): void {
                    // Simulate work
                }
                
                public function isActive(): bool {
                    return true;
                }
                
                public function isShutdownHandler(): bool {
                    return false;
                }
            };
            
            Handler::registerHandler($handler);
        }
        
        // Trigger some exceptions
        $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Test exception'));
        });
        
        $statsBefore = Handler::getMemoryStats();
        $this->assertGreaterThan(0, $statsBefore['handler_count']);
        
        // Shutdown system
        Handler::shutdown();
        
        // Create new instance to check cleanup
        Handler::reset(); // This will create a new instance
        $statsAfter = Handler::getMemoryStats();
        
        // Should have only the default handler
        $this->assertEquals(1, $statsAfter['handler_count']);
        $this->assertEquals(0, $statsAfter['execution_counters']);
    }
    
    /**
     * Test performance with large number of handlers.
     * 
     * @test
     */
    public function testPerformanceWithManyHandlers(): void {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Create many handlers
        $handlerCount = 50;
        for ($i = 0; $i < $handlerCount; $i++) {
            $handler = new class($i) extends AbstractHandler {
                private $id;
                
                public function __construct($id) {
                    parent::__construct();
                    $this->id = $id;
                    $this->setName('PerfTestHandler' . $id);
                }
                
                public function handle(): void {
                    // Minimal work
                }
                
                public function isActive(): bool {
                    return $this->id % 2 === 0; // Only half are active
                }
                
                public function isShutdownHandler(): bool {
                    return false;
                }
            };
            
            Handler::registerHandler($handler);
        }
        
        // Trigger exception handling
        $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Performance test'));
        });
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Performance should be reasonable
        $this->assertLessThan(1.0, $executionTime); // Less than 1 second
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed); // Less than 10MB
        
        $stats = Handler::getMemoryStats();
        $this->assertEquals($handlerCount + 1, $stats['handler_count']); // +1 for default handler
    }
    
    /**
     * Test automatic cleanup when memory threshold is reached.
     * 
     * @test
     */
    public function testAutomaticCleanupOnThreshold(): void {
        // Set a low threshold for testing
        Handler::setMemoryThreshold(1024); // 1KB (very low for testing)
        
        $handler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('ThresholdTestHandler');
            }
            
            public function handle(): void {
                // Simulate work
            }
            
            public function isActive(): bool {
                return true;
            }
            
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        
        Handler::registerHandler($handler);
        
        // Trigger exception to create execution counter
        $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new Exception('Test exception'));
        });
        
        $this->assertEquals(1, Handler::getHandlerExecutionCount('ThresholdTestHandler'));
        
        // Remove handler (should trigger automatic cleanup due to low threshold)
        Handler::unregisterHandler($handler);
        
        // Execution counter should be cleaned up
        $this->assertEquals(0, Handler::getHandlerExecutionCount('ThresholdTestHandler'));
    }
}
