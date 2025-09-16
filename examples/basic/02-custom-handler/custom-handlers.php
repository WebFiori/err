<?php
/**
 * Additional Custom Handler Examples
 * 
 * This file contains more examples of custom handlers for different use cases.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Config\HandlerConfig;

// Set environment to development to avoid security violations
$config = HandlerConfig::createDevelopmentConfig();
$config->setLogDestination(__DIR__.'/example-log.log');
Handler::setConfig($config);

/**
 * File logging handler
 */
class FileLogHandler extends AbstractHandler {
    private string $logFile;
    
    public function __construct(string $logFile = 'error.log') {
        parent::__construct();
        $this->logFile = $logFile;
        $this->setName('FileLog');
    }
    
    public function handle(): void {
        $logEntry = sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            get_class($this->getException()),
            $this->getMessage(),
            $this->getClass(),
            $this->getLine()
        );
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        echo "Error logged to: {$this->logFile}\n";
    }
    
    public function isActive(): bool {
        return true;
    }
    
    public function isShutdownHandler(): bool {
        return true; // Also handle shutdown errors
    }
}

/**
 * Email notification handler (simulation)
 */
class EmailNotificationHandler extends AbstractHandler {
    private string $adminEmail;
    
    public function __construct(string $adminEmail = 'admin@example.com') {
        parent::__construct();
        $this->adminEmail = $adminEmail;
        $this->setName('EmailNotification');
        $this->setPriority(50);
    }
    
    public function handle(): void {
        // In a real implementation, you would send an actual email
        echo "\n--- EMAIL NOTIFICATION (SIMULATED) ---\n";
        echo "To: {$this->adminEmail}\n";
        echo "Subject: Application Error Occurred\n";
        echo "Body:\n";
        echo "An error occurred in the application:\n";
        echo "Type: " . get_class($this->getException()) . "\n";
        echo "Message: " . $this->getMessage() . "\n";
        echo "File: " . $this->getFile() . "\n";
        echo "Line: " . $this->getLine() . "\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        echo "--- END EMAIL ---\n\n";
    }
    
    public function isActive(): bool {
        // Only send emails for critical errors in production
        return $this->isCriticalError();
    }
    
    public function isShutdownHandler(): bool {
        return false;
    }
    
    private function isCriticalError(): bool {
        $exception = $this->getException();
        return $exception instanceof Error || 
               $exception instanceof ParseError ||
               str_contains(strtolower($this->getMessage()), 'fatal');
    }
}

// Demonstrate the handlers
echo "Additional Custom Handlers Example\n";
echo str_repeat('=', 40) . "\n\n";

// Register handlers
Handler::registerHandler(new FileLogHandler(__DIR__ . '/example.log'));
Handler::registerHandler(new EmailNotificationHandler('developer@example.com'));

echo "Handlers registered. Triggering errors...\n\n";

// Test with different error types
$errors = [
    new RuntimeException('Runtime error for testing'),
    new InvalidArgumentException('Invalid argument error'),
    new Error('Fatal error simulation')
];

foreach ($errors as $i => $error) {
    echo "Error " . ($i + 1) . ":\n";
    Handler::handleException($error);
    echo str_repeat('-', 30) . "\n";
}

echo "\nCheck example.log file for logged errors.\n";
