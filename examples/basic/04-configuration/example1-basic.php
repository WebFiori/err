<?php
/**
 * Example 1: Basic Configuration
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Config\HandlerConfig;

echo "Example 1: Basic Configuration\n";
echo str_repeat('-', 25) . "\n";

$config = new HandlerConfig();
$config->setErrorReporting(E_ALL & ~E_NOTICE);
$config->setDisplayErrors(true);
$config->setDisplayStartupErrors(true);

Handler::setConfig($config);
Handler::registerHandler(new DefaultHandler());

echo "Configuration applied:\n";
echo "- Error Reporting: E_ALL & ~E_NOTICE\n";
echo "- Display Errors: Enabled\n";
echo "- Display Startup Errors: Enabled\n\n";

try {
    throw new Exception('Test exception with basic configuration');
} catch (Exception $e) {
    Handler::handleException($e);
}
