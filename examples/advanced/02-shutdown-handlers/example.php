<?php
/**
 * Shutdown Handlers Example
 * 
 * This example demonstrates shutdown handlers for fatal errors.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Config\HandlerConfig;

// Set environment to development to avoid security violations
Handler::setConfig(HandlerConfig::createDevelopmentConfig());

/**
 * Regular handler that doesn't handle shutdown
 */
class RegularHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('Regular');
        $this->setPriority(100);
    }
    
    public function handle(): void {
        echo "[REGULAR] Handling error: " . $this->getMessage() . "\n";
    }
    
    public function isActive(): bool {
        return true;
    }
    
    public function isShutdownHandler(): bool {
        return false; // This handler doesn't handle shutdown
    }
}

/**
 * Shutdown handler for fatal errors
 */
class ShutdownHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('Shutdown');
        $this->setPriority(90);
    }
    
    public function handle(): void {
        echo "[SHUTDOWN] Fatal error detected: " . $this->getMessage() . "\n";
        echo "[SHUTDOWN] Performing cleanup operations...\n";
        
        // Simulate cleanup operations
        $this->performCleanup();
        
        echo "[SHUTDOWN] Cleanup completed.\n";
    }
    
    public function isActive(): bool {
        return true;
    }
    
    public function isShutdownHandler(): bool {
        return true; // This handler handles shutdown errors
    }
    
    private function performCleanup(): void {
        // Simulate cleanup operations
        echo "[SHUTDOWN] - Closing database connections\n";
        echo "[SHUTDOWN] - Saving temporary data\n";
        echo "[SHUTDOWN] - Releasing resources\n";
    }
}

/**
 * Memory cleanup handler
 */
class MemoryCleanupHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('MemoryCleanup');
        $this->setPriority(95);
    }
    
    public function handle(): void {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        
        echo "[MEMORY] Current memory usage: " . $this->formatBytes($memoryUsage) . "\n";
        echo "[MEMORY] Memory limit: " . $this->formatBytes($memoryLimit) . "\n";
        
        if ($memoryUsage > ($memoryLimit * 0.9)) {
            echo "[MEMORY] WARNING: High memory usage detected!\n";
            echo "[MEMORY] Attempting memory cleanup...\n";
            
            // Force garbage collection
            gc_collect_cycles();
            
            $newMemoryUsage = memory_get_usage(true);
            echo "[MEMORY] Memory after cleanup: " . $this->formatBytes($newMemoryUsage) . "\n";
        }
    }
    
    public function isActive(): bool {
        return true;
    }
    
    public function isShutdownHandler(): bool {
        return true;
    }
    
    private function getMemoryLimit(): int {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $limit
        };
    }
    
    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

echo "WebFiori Error Handler - Shutdown Handlers Example\n";
echo str_repeat('=', 55) . "\n\n";

// Register both regular and shutdown handlers
Handler::registerHandler(new RegularHandler());
Handler::registerHandler(new ShutdownHandler());
Handler::registerHandler(new MemoryCleanupHandler());

echo "Registered handlers:\n";
echo "- RegularHandler (not shutdown handler)\n";
echo "- ShutdownHandler (shutdown handler)\n";
echo "- MemoryCleanupHandler (shutdown handler)\n\n";

// Test 1: Regular Exception
echo "Test 1: Regular Exception\n";
echo str_repeat('-', 25) . "\n";

try {
    throw new Exception('This is a regular exception');
} catch (Exception $e) {
    Handler::handleException($e);
}

echo "\nNote: All handlers processed the regular exception.\n\n";

// Test 2: Simulated Fatal Error
echo "Test 2: Simulated Fatal Error\n";
echo str_repeat('-', 30) . "\n";

try {
    throw new Error('Simulated fatal error');
} catch (Error $e) {
    Handler::handleException($e);
}

echo "\nNote: All handlers processed the fatal error.\n\n";

// Test 3: Memory-related Error
echo "Test 3: Memory-related Error\n";
echo str_repeat('-', 28) . "\n";

try {
    throw new Exception('Memory allocation failed - out of memory');
} catch (Exception $e) {
    Handler::handleException($e);
}

echo "\nShutdown handlers are particularly useful for:\n";
echo "- Fatal errors that would normally terminate the script\n";
echo "- Memory exhaustion scenarios\n";
echo "- Resource cleanup during unexpected termination\n";
echo "- Logging critical errors before shutdown\n";
echo "- Graceful degradation in error conditions\n\n";

echo "Shutdown handlers example completed! \n";
echo "Output will be shown after this line as php shutdown \n";
