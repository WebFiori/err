<?php
/**
 * Example 3: SQL Injection Attempt Sanitization
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Config\HandlerConfig;

Handler::setConfig(HandlerConfig::createDevelopmentConfig());
Handler::registerHandler(new DefaultHandler());

echo "Test 3: SQL Injection Attempt Sanitization\n";
echo str_repeat('-', 43) . "\n";

$maliciousInputs = [
    "SELECT * FROM users WHERE id=1; DROP TABLE users;--",
    "'; DELETE FROM accounts; --",
    "UNION SELECT password FROM admin_users",
    "<script>alert('XSS')</script>",
    "../../etc/passwd"
];

foreach ($maliciousInputs as $i => $input) {
    echo "Malicious input " . ($i + 1) . ": {$input}\n";
    
    try {
        throw new Exception("Database error with input: {$input}");
    } catch (Exception $e) {
        Handler::handleException($e);
    }
    
    echo str_repeat('.', 50) . "\n";
}
