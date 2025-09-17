<?php
/**
 * CLI vs HTTP Output Example
 * 
 * This example shows how the handler adapts output based on execution context.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\Config\HandlerConfig;

// Set environment to development to avoid security violations
Handler::setConfig(HandlerConfig::createDevelopmentConfig());

// Detect execution context
$isCLI = http_response_code() === false;

if ($isCLI) {
    echo "WebFiori Error Handler - CLI vs HTTP Example\n";
    echo str_repeat('=', 50) . "\n\n";
    echo "Running in CLI mode - you'll see terminal-formatted output\n\n";
} else {
    echo "<!DOCTYPE html>\n";
    echo "<html><head><title>WebFiori Error Handler - CLI vs HTTP Example</title></head><body>\n";
    echo "<h1>WebFiori Error Handler - CLI vs HTTP Example</h1>\n";
    echo "<p>Running in HTTP mode - you'll see HTML-formatted output</p>\n";
}

// Register the default handler (automatically detects CLI vs HTTP)
$handler = new DefaultHandler();
Handler::registerHandler($handler);

if ($isCLI) {
    echo "Handler registered. Context detected: CLI\n";
    echo "Triggering exception to see CLI-formatted output...\n\n";
} else {
    echo "<p>Handler registered. Context detected: HTTP</p>\n";
    echo "<p>Triggering exception to see HTML-formatted output...</p>\n";
}

// Trigger an exception to see context-appropriate formatting
try {
    throw new Exception('This exception will be formatted based on the execution context (CLI or HTTP)');
} catch (Exception $e) {
    Handler::handleException($e);
}

if ($isCLI) {
    echo "\nAs you can see, the output is formatted for terminal display with:\n";
    echo "- Plain text formatting\n";
    echo "- ANSI color codes (if supported)\n";
    echo "- Line-based layout\n";
    echo "- Terminal-friendly separators\n\n";
    echo "Try running this same script via a web server to see HTML output!\n";
} else {
    echo "<hr>\n";
    echo "<h3>HTTP Mode Features:</h3>\n";
    echo "<ul>\n";
    echo "<li>HTML structure with proper tags</li>\n";
    echo "<li>CSS styling for better readability</li>\n";
    echo "<li>Collapsible sections for stack traces</li>\n";
    echo "<li>Browser-friendly formatting</li>\n";
    echo "</ul>\n";
    echo "<p>Try running this script from the command line to see CLI output!</p>\n";
    echo "</body></html>\n";
}
