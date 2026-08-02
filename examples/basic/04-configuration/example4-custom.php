<?php
/**
 * Example 4: Custom Configuration
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Config\HandlerConfig;

echo "Example 4: Custom Configuration\n";
echo str_repeat('-', 25) . "\n";

$customConfig = new HandlerConfig();
$customConfig->setErrorReporting(E_ERROR | E_WARNING | E_PARSE);
$customConfig->setDisplayErrors(true);
$customConfig->setModifyGlobalSettings(false);


Handler::setConfig($customConfig);
Handler::registerHandler(new DefaultHandler());

echo "Custom configuration applied:\n";
echo "- Error Reporting: E_ERROR | E_WARNING | E_PARSE\n";
echo "- Display Errors: Enabled\n";
echo "- Modify Global Settings: Disabled\n\n";

try {
    throw new LogicException('Test exception with custom configuration');
} catch (LogicException $e) {
    Handler::handleException($e);
}
