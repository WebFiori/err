# Errors and Exceptions Handler Component of WebFiori Framework

A comprehensive, secure, and production-ready library for handling PHP errors and exceptions with advanced security features, memory management, and enterprise-grade reliability.

<p align="center">
  <a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php83.yaml">
    <img src="https://github.com/WebFiori/err/actions/workflows/php83.yaml/badge.svg?branch=main">
  </a>
  <a href="https://sonarcloud.io/dashboard?id=WebFiori_err">
      <img src="https://sonarcloud.io/api/project_badges/measure?project=WebFiori_err&metric=alert_status" />
  </a>
  <a href="https://packagist.org/packages/webfiori/err">
    <img src="https://img.shields.io/packagist/dt/webfiori/err?color=light-green">
  </a>
</p>

## Supported PHP Versions
|                                                                                       Build Status                                                                                       |
|:----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------:|
| <a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php81.yaml"><img src="https://github.com/WebFiori/err/actions/workflows/php81.yaml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php82.yaml"><img src="https://github.com/WebFiori/err/actions/workflows/php82.yaml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php83.yaml"><img src="https://github.com/WebFiori/err/actions/workflows/php83.yaml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php84.yaml"><img src="https://github.com/WebFiori/err/actions/workflows/php84.yaml/badge.svg?branch=main"></a>  |

## Installation
The library can be included in your project by adding the following entry to the `require` section of your `composer.json`:

```json
{
    "require": {
        "webfiori/err": "*"
    }
}
```

Or install via Composer command:

```bash
composer require webfiori/err
```

## Testing

The library includes a comprehensive test suite with 68 tests covering all functionality:

```bash
# Run all tests
composer test

# Run tests with clean output (no HTML/JSON noise)
composer test-clean
```

The `test-clean` command provides a clean, professional output suitable for development and CI/CD environments by filtering out verbose HTML error blocks and JSON logs while preserving all PHPUnit results and assertions.

## Features
* **Comprehensive Error Handling**: Conversion of all PHP errors to exceptions with advanced filtering and sanitization
* **Security-First Design**: Built-in output sanitization, secure configuration management, and production-ready security features
* **Memory Management**: Advanced memory optimization with cleanup mechanisms, performance caching, and leak prevention
* **Infinite Loop Protection**: Robust protection against handler execution cycles with configurable limits and safe fallbacks
* **Enterprise-Grade Reliability**: Production-tested stability features with comprehensive error isolation and recovery
* **Flexible Handler System**: Object-oriented abstraction for `set_exception_handler()` with priority-based handler registration
* **Environment-Aware Configuration**: Automatic environment detection with development and production presets
* **Performance Optimized**: TraceEntry caching, memory threshold monitoring, and garbage collection integration

## Usage

The library provides a robust foundation for error handling with two main classes: `Handler` and `AbstractExceptionHandler`. The `Handler` class serves as the core component for registering custom exception handlers, while `AbstractExceptionHandler` provides the base class for implementing custom handlers. Since the library converts all PHP errors to exceptions, there's no need for separate error handlers.

### Implementing a Custom Exceptions Handler

Creating a custom exceptions handler is straightforward. Simply extend the `AbstractExceptionHandler` class and implement the required abstract methods. The `handle()` method is where you define how exceptions should be processed. The library includes a default handler that serves as a good reference implementation.

```php
<?php
namespace WebFiori\Error;

class DefaultExceptionsHandler extends AbstractExceptionHandler {
    public function __construct() {
        parent::__construct();

        // Set handler name. Each registered handler must have a unique name.
        $this->setName('Production Handler');

        // Sets the priority of the handler. Higher values = higher priority.
        $this->setPriority(100);
    }

    /**
     * Handles the exception with security and performance considerations.
     */
    public function handle() {
        // Use the built-in security features for safe output
        $sanitizer = $this->getOutputSanitizer();
        
        echo '<pre>';
        echo 'An exception was thrown at ' . $sanitizer->sanitize($this->getClass()) . 
             ' line ' . $this->getLine() . '.<br>';
        echo 'Exception message: ' . $sanitizer->sanitize($this->getMessage()) . '.<br>';
        echo 'Stack trace:<br>';
        
        $trace = $this->getTrace();
        
        if (count($trace) == 0) {
            echo '&lt;Empty&gt;';
        } else {
            $index = 0;
            foreach ($trace as $entry) {
                echo '#' . $index . ' ' . $sanitizer->sanitize($entry) . '<br>';
                $index++;
            }
        }
        echo '</pre>';
    }

    public function isActive(): bool {
        // Activate or deactivate the handler based on conditions
        return true;
    }

    public function isShutdownHandler(): bool {
        // Set the handler as shutdown handler (for errors after processing)
        return false;
    }
}
```

### Setting a Custom Exceptions Handler

After implementing your handler, register it using the `Handler::registerHandler()` method. The library supports multiple handlers with priority-based execution.

```php
// Register a single handler
Handler::registerHandler(new DefaultExceptionsHandler());

// Register multiple handlers with different priorities
Handler::registerHandler(new ProductionHandler());
Handler::registerHandler(new DevelopmentHandler());
Handler::registerHandler(new LoggingHandler());
```

### Advanced Configuration

The library includes enterprise-grade configuration options for production environments:

```php
use WebFiori\Error\HandlerConfig;

// Create a production-ready configuration
$config = HandlerConfig::createProductionConfig();

// Or customize settings
$config = new HandlerConfig();
$config->setEnvironment('production')
       ->setMemoryLimit(50 * 1024 * 1024) // 50MB
       ->setMaxExecutionCount(3)
       ->enableInfiniteLoopProtection()
       ->enableMemoryManagement();

// Apply configuration to handlers
Handler::setGlobalConfig($config);
```

### Security Features

The library includes built-in security features that automatically sanitize output and protect against information disclosure:

```php
// Output sanitization is automatic in production mode
$handler = new MyCustomHandler();

// Access the sanitizer directly if needed
$sanitizer = $handler->getOutputSanitizer();
$safeOutput = $sanitizer->sanitize($potentiallyUnsafeData);
```

### Memory Management

For long-running processes, the library provides automatic memory management:

```php
// Memory cleanup is automatic, but can be triggered manually
Handler::performMemoryCleanup();

// Monitor memory usage
$stats = Handler::getMemoryStats();
echo "Current usage: " . $stats['current'] . " bytes";
echo "Peak usage: " . $stats['peak'] . " bytes";
```

## License

This library is licensed under the MIT License. See the LICENSE file for details.
