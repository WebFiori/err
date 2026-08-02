<?php
/**
 * Simple Usage Example
 * 
 * This example shows the most basic usage of WebFiori Error Handler.
 * The library automatically registers itself and handles errors.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Config\HandlerConfig;

echo "WebFiori Error Handler - Simple Usage Example\n";
echo str_repeat('=', 50) . "\n\n";

// The handler system starts automatically when first accessed
// Register the default handler with development security level

Handler::registerHandler(new DefaultHandler());

// Set environment to development to avoid security violations
Handler::setConfig(HandlerConfig::createDevelopmentConfig());

echo "Handler registered successfully!\n";
echo "Now triggering an exception to see the handler in action...\n\n";


throw new Exception('This is a sample exception to demonstrate error handling');

