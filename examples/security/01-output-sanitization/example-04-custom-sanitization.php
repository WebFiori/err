<?php
/**
 * Example 4: Custom Sanitization Handler
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\Config\HandlerConfig;

Handler::setConfig(HandlerConfig::createDevelopmentConfig());

echo "Test 4: Custom Sanitization Handler\n";
echo str_repeat('-', 35) . "\n";

class CustomSanitizationHandler extends \WebFiori\Error\AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('CustomSanitization');
    }
    
    public function handle(): void {
        $originalMessage = $this->getException()->getMessage();
        $sanitizedMessage = $this->getMessage();
        
        echo "=== Custom Sanitization Handler ===\n";
        echo "Original message length: " . strlen($originalMessage) . " characters\n";
        echo "Sanitized message length: " . strlen($sanitizedMessage) . " characters\n";
        echo "Sanitized message: {$sanitizedMessage}\n";
        echo "File: " . $this->getFile() . "\n";
        echo "Line: " . $this->getLine() . "\n";
        
        $extraSanitized = $this->performExtraSanitization($sanitizedMessage);
        echo "Extra sanitized: {$extraSanitized}\n";
        echo "===================================\n";
    }
    
    private function performExtraSanitization(string $message): string {
        $sanitized = preg_replace('/\b\d{3,}\b/', '[NUMBER]', $message);
        $sanitized = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', '[EMAIL]', $sanitized);
        return $sanitized;
    }
    
    public function isActive(): bool { return true; }
    public function isShutdownHandler(): bool { return false; }
}

Handler::reset();
Handler::registerHandler(new CustomSanitizationHandler());

try {
    throw new Exception('Error with email user@example.com and ID 123456789');
} catch (Exception $e) {
    Handler::handleException($e);
}
