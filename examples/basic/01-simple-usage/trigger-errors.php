<?php
/**
 * Error Triggering Script
 * 
 * This script demonstrates different types of errors that can be handled.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Config\HandlerConfig;

// Register the default handler
Handler::registerHandler(new DefaultHandler());

// Set environment to development to avoid security violations
Handler::setConfig(HandlerConfig::createDevelopmentConfig());

echo "Triggering different types of errors...\n\n";

// 1. Exception
echo "1. Triggering Exception:\n";
try {
    throw new Exception('Sample exception message');
} catch (Exception $e) {
    Handler::invokeExceptionsHandler($e);
}

echo "\n" . str_repeat('-', 40) . "\n\n";

// 2. Runtime Error
echo "2. Triggering Runtime Error:\n";
try {
    throw new RuntimeException('Runtime error occurred');
} catch (RuntimeException $e) {
    Handler::invokeExceptionsHandler($e);
}

echo "\n" . str_repeat('-', 40) . "\n\n";

// 3. Invalid Argument Exception
echo "3. Triggering Invalid Argument Exception:\n";
try {
    throw new InvalidArgumentException('Invalid argument provided');
} catch (InvalidArgumentException $e) {
    Handler::invokeExceptionsHandler($e);
}

echo "\n\nAll error types demonstrated successfully!\n";
