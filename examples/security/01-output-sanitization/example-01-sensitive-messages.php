<?php
/**
 * Example 1: Sensitive Information in Messages
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Config\HandlerConfig;

Handler::setConfig(HandlerConfig::createDevelopmentConfig());
Handler::registerHandler(new DefaultHandler());

echo "Test 1: Sensitive Information in Messages\n";
echo str_repeat('-', 42) . "\n";

$sensitiveMessages = [
    'Database connection failed: password=secret123',
    'API key validation error: api_key=sk_live_abc123xyz',
    'Authentication failed for user: token=bearer_xyz789',
    'Configuration error: secret_key=my_secret_key_value',
    'Session error: session_id=sess_abc123def456'
];

foreach ($sensitiveMessages as $i => $message) {
    echo "Original message " . ($i + 1) . ": {$message}\n";
    
    try {
        throw new Exception($message);
    } catch (Exception $e) {
        Handler::handleException($e);
    }
    
    echo str_repeat('.', 50) . "\n";
}
