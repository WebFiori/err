<?php
/**
 * Custom Handler Example
 * 
 * This example shows how to create and register custom error handlers.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Config\HandlerConfig;

// Set environment to development to avoid security violations
Handler::setConfig(HandlerConfig::createDevelopmentConfig());

/**
 * Simple custom handler that formats errors in a basic way
 */
class SimpleCustomHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('SimpleCustom');
        $this->setPriority(100); // High priority
    }
    
    public function handle(): void {
        echo "\n" . str_repeat('*', 60) . "\n";
        echo "CUSTOM ERROR HANDLER ACTIVATED\n";
        echo str_repeat('*', 60) . "\n";
        echo "Error Type: " . get_class($this->getException()) . "\n";
        echo "Message: " . $this->getMessage() . "\n";
        echo "Location: " . $this->getClass() . " (Line " . $this->getLine() . ")\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('*', 60) . "\n\n";
    }
    
    public function isActive(): bool {
        return true; // Always active
    }
    
    public function isShutdownHandler(): bool {
        return false; // Don't handle shutdown errors
    }
}

/**
 * JSON formatter handler for API responses
 */
class JsonHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('JSON');
        $this->setPriority(90);
    }
    
    public function handle(): void {
        $errorData = [
            'error' => true,
            'type' => get_class($this->getException()),
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'timestamp' => date('c'),
            'trace' => array_map(function($entry) {
                return (string)$entry;
            }, $this->getTrace())
        ];
        
        echo "\nJSON Handler Output:\n";
        echo json_encode($errorData, JSON_PRETTY_PRINT) . "\n\n";
    }
    
    public function isActive(): bool {
        // Only active if we're handling API requests
        return isset($_SERVER['HTTP_ACCEPT']) && 
               str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
    }
    
    public function isShutdownHandler(): bool {
        return false;
    }
}

echo "WebFiori Error Handler - Custom Handler Example\n";
echo str_repeat('=', 50) . "\n\n";

// Register our custom handlers
Handler::registerHandler(new SimpleCustomHandler());
Handler::registerHandler(new JsonHandler());

echo "Custom handlers registered!\n";
echo "Triggering an exception to see custom handling...\n";

// Trigger an exception to see our custom handlers in action
try {
    throw new RuntimeException('This error will be handled by our custom handlers');
} catch (RuntimeException $e) {
    Handler::handleException($e);
}

echo "Custom handler example completed!\n";
