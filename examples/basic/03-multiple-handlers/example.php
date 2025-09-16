<?php
/**
 * Multiple Handlers Example
 * 
 * This example shows how to use multiple handlers with different priorities.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Config\HandlerConfig;
use WebFiori\Error\DefaultHandler;

// Set environment to development to avoid security violations
Handler::setConfig(HandlerConfig::createDevelopmentConfig());

/**
 * High priority display handler
 */
class DisplayHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('Display');
        $this->setPriority(100); // Highest priority
    }
    
    public function handle(): void {
        echo "\n=== DISPLAY HANDLER (Priority: 100) ===\n";
        echo "Error: " . $this->getMessage() . "\n";
        echo "Location: " . $this->getClass() . ":" . $this->getLine() . "\n";
        echo "========================================\n";
    }
    
    public function isActive(): bool {
        return true;
    }
    
    public function isShutdownHandler(): bool {
        return false;
    }
}

/**
 * Medium priority logging handler
 */
class LoggingHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('Logging');
        $this->setPriority(50); // Medium priority
    }
    
    public function handle(): void {
        echo "\n--- LOGGING HANDLER (Priority: 50) ---\n";
        $logEntry = sprintf(
            "[%s] %s in %s:%d - %s",
            date('Y-m-d H:i:s'),
            get_class($this->getException()),
            $this->getFile(),
            $this->getLine(),
            $this->getMessage()
        );
        echo "Logged: " . $logEntry . "\n";
        echo "--------------------------------------\n";
    }
    
    public function isActive(): bool {
        return true;
    }
    
    public function isShutdownHandler(): bool {
        return true;
    }
}

/**
 * Low priority notification handler
 */
class NotificationHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('Notification');
        $this->setPriority(10); // Lowest priority
    }
    
    public function handle(): void {
        echo "\n... NOTIFICATION HANDLER (Priority: 10) ...\n";
        echo "Notification sent to admin about error\n";
        echo "Error severity: " . $this->getErrorSeverity() . "\n";
        echo "...............................................\n";
    }
    
    public function isActive(): bool {
        // Only notify for serious errors
        return $this->isSeriousError();
    }
    
    public function isShutdownHandler(): bool {
        return false;
    }
    
    private function isSeriousError(): bool {
        $exception = $this->getException();
        return $exception instanceof Error || 
               str_contains(strtolower($this->getMessage()), 'critical');
    }
    
    private function getErrorSeverity(): string {
        $exception = $this->getException();
        if ($exception instanceof Error) {
            return 'CRITICAL';
        }
        if ($exception instanceof RuntimeException) {
            return 'HIGH';
        }
        return 'MEDIUM';
    }
}

/**
 * Conditional handler that only activates in development
 */
class DebugHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('Debug');
        $this->setPriority(75); // High priority for debugging
    }
    
    public function handle(): void {
        echo "\n### DEBUG HANDLER (Priority: 75) ###\n";
        echo "Stack Trace:\n";
        foreach ($this->getTrace() as $i => $entry) {
            echo "  #{$i} " . (string)$entry . "\n";
        }
        echo "Memory Usage: " . number_format(memory_get_usage(true)) . " bytes\n";
        echo "################################\n";
    }
    
    public function isActive(): bool {
        // Only active in development environment
        return $this->isDevelopmentEnvironment();
    }
    
    public function isShutdownHandler(): bool {
        return false;
    }
    
    private function isDevelopmentEnvironment(): bool {
        return !isset($_ENV['PRODUCTION']) || $_ENV['PRODUCTION'] !== 'true';
    }
}

echo "WebFiori Error Handler - Multiple Handlers Example\n";
echo str_repeat('-+', 30) . "\n\n";

// Register handlers in different order (priority determines execution order)
Handler::registerHandler(new NotificationHandler());  // Priority: 10
Handler::registerHandler(new DisplayHandler());       // Priority: 100
Handler::registerHandler(new LoggingHandler());       // Priority: 50
Handler::registerHandler(new DebugHandler());         // Priority: 75

//Remove Default Handler
Handler::unregisterHandlerByClassName(DefaultHandler::class);

echo "Registered 4 handlers with different priorities:\n";
echo "- DisplayHandler (Priority: 100)\n";
echo "- DebugHandler (Priority: 75)\n";
echo "- LoggingHandler (Priority: 50)\n";
echo "- NotificationHandler (Priority: 10)\n\n";

echo "Triggering a regular exception...\n";

try {
    throw new RuntimeException('This is a regular runtime exception');
} catch (RuntimeException $e) {
    Handler::handleException($e);
}

echo "\n" . str_repeat('-+', 30) . "\n";
echo "Triggering a critical error...\n";
echo  str_repeat('-+', 30) . "\n";

try {
    throw new Error('This is a critical error');
} catch (Error $e) {
    Handler::handleException($e);
}

echo "\nNotice how different handlers activate based on error type and conditions!\n";
echo "The LoggingHandler will execute during shutdown phase after this statement.\n";
