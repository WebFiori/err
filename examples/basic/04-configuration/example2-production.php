<?php
/**
 * Example 2: Production Configuration
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Config\HandlerConfig;

echo "Example 2: Production Configuration\n";
echo str_repeat('-', 30) . "\n";

$productionConfig = HandlerConfig::createProductionConfig();
Handler::setConfig($productionConfig);
Handler::registerHandler(new DefaultHandler());

echo "Production configuration applied:\n";
echo "- Error Reporting: E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR\n";
echo "- Display Errors: Disabled\n";
echo "- Display Startup Errors: Disabled\n\n";

try {
    throw new RuntimeException('Test exception with production configuration');
} catch (RuntimeException $e) {
    Handler::handleException($e);
}
