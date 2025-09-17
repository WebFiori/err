<?php
/**
 * Example 2: File Path Sanitization
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Security\SecurityConfig;
use WebFiori\Error\Config\HandlerConfig;

Handler::setConfig(HandlerConfig::createDevelopmentConfig());
Handler::registerHandler(new DefaultHandler());

echo "Test 2: File Path Sanitization\n";
echo str_repeat('-', 30) . "\n";

$securityLevels = ['development', 'staging', 'production'];

foreach ($securityLevels as $level) {
    echo "Security Level: {$level}\n";
    
    $securityConfig = new SecurityConfig($level);
    
    echo "- Show full paths: " . ($securityConfig->shouldShowFullPaths() ? 'Yes' : 'No') . "\n";
    echo "- Show stack trace: " . ($securityConfig->shouldShowStackTrace() ? 'Yes' : 'No') . "\n";
    
    try {
        throw new Exception("Error in sensitive file path");
    } catch (Exception $e) {
        Handler::handleException($e);
    }
    
    echo str_repeat('.', 30) . "\n";
}
