<?php
/**
 * Example 3: Development Configuration
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Config\HandlerConfig;

echo "Example 3: Development Configuration\n";
echo str_repeat('-', 32) . "\n";

$developmentConfig = HandlerConfig::createDevelopmentConfig();
Handler::setConfig($developmentConfig);
Handler::registerHandler(new DefaultHandler());

echo "Development configuration applied:\n";
echo "- Error Reporting: E_ALL\n";
echo "- Display Errors: Enabled\n";
echo "- Display Startup Errors: Enabled\n\n";

try {
    throw new InvalidArgumentException('Test exception with development configuration');
} catch (InvalidArgumentException $e) {
    Handler::handleException($e);
}
