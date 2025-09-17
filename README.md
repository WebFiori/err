# WebFiori Error Handler

A comprehensive, secure, and production-ready PHP library for handling errors and exceptions with advanced security features, CLI/HTTP awareness, memory management, and enterprise-grade reliability.

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

## Features

* **🛡️ Security-First Design**: Built-in output sanitization, secure configuration management, and production-ready security features
* **🔄 Comprehensive Error Handling**: Converts all PHP errors to exceptions with advanced filtering and sanitization
* **🧠 Memory Management**: Advanced memory optimization with cleanup mechanisms, performance caching, and leak prevention
* **⚡ Performance Optimized**: TraceEntry caching, memory threshold monitoring, and garbage collection integration
* **🎯 Flexible Handler System**: Object-oriented abstraction with priority-based handler registration
* **🌍 Environment-Aware**: Automatic environment detection with development and production presets
* **🔧 Null Safety**: Robust null handling prevents TypeErrors in edge cases
* **📊 Comprehensive Testing**: 92 tests with 100% pass rate ensuring reliability

## Installation

Install via Composer:

```bash
composer require webfiori/err
```

Or add to your `composer.json`:

```json
{
    "require": {
        "webfiori/err": "^2.0"
    }
}
```

## Quick Start

### Basic Usage

```php
<?php
require_once 'vendor/autoload.php';

use WebFiori\Error\Handler;

// The handler system starts automatically when first accessed
// Register the default handler (this happens automatically)
Handler::registerHandler(new \WebFiori\Error\DefaultHandler());

// Your application code - errors will be caught automatically
throw new Exception('Something went wrong!');
```

### Custom Handler

```php
<?php
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Handler;

class MyCustomHandler extends AbstractHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('My Custom Handler');
        $this->setPriority(100);
    }

    public function handle(): void {
        // Get sanitized exception information using built-in methods
        $message = $this->getMessage(); // Already sanitized
        $location = $this->getClass() . ' line ' . $this->getLine();
        
        // Output using secure output method
        $this->secureOutput("ERROR: {$message}\n");
        $this->secureOutput("Location: {$location}\n");
    }

    public function isActive(): bool {
        return true;
    }

    public function isShutdownHandler(): bool {
        return false;
    }
}

// Register and the system will use it automatically
Handler::registerHandler(new MyCustomHandler());
```

## Detailed Usage Guide

### Handler Registration

The library supports multiple handlers with priority-based execution:

```php
use WebFiori\Error\Handler;
use WebFiori\Error\DefaultHandler;

// Register multiple handlers
Handler::registerHandler(new ProductionHandler(), 100);  // Highest priority
Handler::registerHandler(new LoggingHandler(), 50);      // Medium priority  
Handler::registerHandler(new DefaultHandler(), 10);      // Lowest priority

// The system starts automatically when handlers are registered
```

### Environment-Aware Configuration

The library automatically detects CLI vs HTTP environments and formats output appropriately:

```php
use WebFiori\Error\Config\HandlerConfig;
use WebFiori\Error\Handler;

// Production configuration
$config = HandlerConfig::createProductionConfig();

// Development configuration  
$config = HandlerConfig::createDevelopmentConfig();

// Custom configuration
$config = new HandlerConfig();
$config->setErrorReporting(E_ALL)
       ->setDisplayErrors(false)
       ->setModifyGlobalSettings(true);

// Apply configuration
Handler::setConfig($config);
```

### Security Features

#### Output Sanitization

```php
use WebFiori\Error\AbstractHandler;

class SecureHandler extends AbstractHandler {
    public function handle(): void {
        // Automatically sanitized methods
        $safeMessage = $this->getMessage(); // Already sanitized
        $safeClass = $this->getClass(); // Already sanitized
        
        // Use secure output method
        $this->secureOutput("Error: {$safeMessage}");
    }
}
```

#### Security Configuration

```php
use WebFiori\Error\Security\SecurityConfig;

// Create security config for different environments
$devConfig = new SecurityConfig('development');   // Shows full details
$prodConfig = new SecurityConfig('production');   // Minimal safe output
$stagingConfig = new SecurityConfig('staging');   // Balanced approach

// Custom security settings
$customConfig = new SecurityConfig('custom');
// Note: SecurityConfig uses internal methods for configuration
```

### Memory Management

For long-running processes and high-traffic applications:

```php
use WebFiori\Error\Handler;
use WebFiori\Error\Config\HandlerConfig;

// Enable automatic memory management
$config = new HandlerConfig();
$config->setModifyGlobalSettings(true);

Handler::setConfig($config);

// Manual memory operations
Handler::cleanupMemory();

// Monitor memory usage
$stats = Handler::getMemoryStats();
echo "Current: " . number_format($stats['current']) . " bytes\n";
echo "Peak: " . number_format($stats['peak']) . " bytes\n";
```

### Infinite Loop Protection

Prevent handler execution cycles:

```php
use WebFiori\Error\Config\HandlerConfig;
use WebFiori\Error\Handler;

$config = new HandlerConfig();
$config->setModifyGlobalSettings(true);

Handler::setConfig($config);

// Set maximum handler executions
Handler::setMaxHandlerExecutions(3); // Max 3 handler executions per request
```

### Logging Integration

The library integrates with PHP's error logging system:

```php
use WebFiori\Error\AbstractHandler;

class LoggingHandler extends AbstractHandler {
    public function handle(): void {
        // Secure logging with automatic sanitization
        $this->secureLog('Exception occurred', [
            'class' => $this->getClass(),
            'line' => $this->getLine(),
            'code' => $this->getCode()
        ]);
        
        // Log security violations
        if ($this->detectSecurityIssue()) {
            $this->logSecurityViolation('Suspicious exception pattern detected');
        }
    }
    
    private function detectSecurityIssue(): bool {
        // Your security detection logic
        return false;
    }
}
```

### CLI vs HTTP Output Examples

#### CLI Output (Automatic Detection)
```
============================================================
APPLICATION ERROR
============================================================
Location: MyClass line 42
Message: Database connection failed

Stack Trace:
----------------------------------------
#0 At class MyClass line 42
#1 At class Application line 15
----------------------------------------

This detailed error information is shown because you are in development mode.
Time: 2024-01-15 10:30:45
============================================================
```

#### HTTP Output (Automatic Detection)
```html
<div class="error-container">
    <h3 class="error-title">Application Error</h3>
    <p><strong>Location:</strong> MyClass line 42</p>
    <p><strong>Message:</strong> Database connection failed</p>
    
    <details class="error-trace">
        <summary><strong>Stack Trace</strong></summary>
        <pre class="error-trace-content">
#0 At class MyClass line 42
#1 At class Application line 15
        </pre>
    </details>
    
    <p class="error-help">This detailed error information is shown because you are in development mode.</p>
    <p class="error-timestamp">Time: 2024-01-15 10:30:45</p>
</div>
```

## Advanced Examples

### Production-Ready Handler

```php
<?php
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Security\SecurityConfig;

class ProductionHandler extends AbstractHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('Production Error Handler');
        $this->setPriority(100);
    }

    public function createSecurityConfig(): SecurityConfig {
        return new SecurityConfig('production');
    }

    public function handle(): void {
        // Log the error securely
        $this->secureLog('Production error occurred', [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI'
        ]);

        // Show minimal user-friendly message
        if (php_sapi_name() === 'cli') {
            echo "\033[1;31mAn error occurred. Please check the logs.\033[0m\n";
        } else {
            echo '<div style="color: red; padding: 20px; border: 1px solid red;">';
            echo '<h3>Service Temporarily Unavailable</h3>';
            echo '<p>We are experiencing technical difficulties. Please try again later.</p>';
            echo '</div>';
        }
    }

    public function isActive(): bool {
        return $_ENV['APP_ENV'] === 'production';
    }

    public function isShutdownHandler(): bool {
        return true; // Handle fatal errors
    }
}
```

### Development Handler with Enhanced Features

```php
<?php
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Security\SecurityConfig;

class DevelopmentHandler extends AbstractHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('Development Error Handler');
        $this->setPriority(90);
    }

    public function createSecurityConfig(): SecurityConfig {
        return new SecurityConfig('development');
    }

    public function handle(): void {
        if (php_sapi_name() === 'cli') {
            $this->renderCLIOutput();
        } else {
            $this->renderHTMLOutput();
        }
    }

    private function renderCLIOutput(): void {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "\033[1;31mDEVELOPMENT ERROR\033[0m\n";
        echo str_repeat('=', 60) . "\n";
        echo "\033[1mClass:\033[0m " . $this->getClass() . "\n";
        echo "\033[1mLine:\033[0m " . $this->getLine() . "\n";
        echo "\033[1mMessage:\033[0m " . $this->getMessage() . "\n";
        
        if ($this->getCode() !== '0') {
            echo "\033[1mCode:\033[0m " . $this->getCode() . "\n";
        }
        
        echo "\n\033[1mStack Trace:\033[0m\n";
        echo str_repeat('-', 40) . "\n";
        
        foreach ($this->getTrace() as $index => $entry) {
            echo "#{$index} " . (string)$entry . "\n";
        }
        
        echo str_repeat('=', 60) . "\n\n";
    }

    private function renderHTMLOutput(): void {
        echo '<div style="font-family: monospace; background: #f8f8f8; padding: 20px; border-left: 5px solid #ff0000;">';
        echo '<h2 style="color: #ff0000; margin-top: 0;">Development Error</h2>';
        echo '<p><strong>Class:</strong> ' . htmlspecialchars($this->getClass()) . '</p>';
        echo '<p><strong>Line:</strong> ' . $this->getLine() . '</p>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($this->getMessage()) . '</p>';
        
        if ($this->getCode() !== '0') {
            echo '<p><strong>Code:</strong> ' . $this->getCode() . '</p>';
        }
        
        echo '<details style="margin-top: 15px;">';
        echo '<summary><strong>Stack Trace</strong></summary>';
        echo '<pre style="background: #fff; padding: 10px; overflow-x: auto;">';
        
        foreach ($this->getTrace() as $index => $entry) {
            echo "#{$index} " . htmlspecialchars((string)$entry) . "\n";
        }
        
        echo '</pre></details></div>';
    }

    public function isActive(): bool {
        return $_ENV['APP_ENV'] === 'development' || $_ENV['APP_DEBUG'] === 'true';
    }

    public function isShutdownHandler(): bool {
        return false;
    }
}
```

## Testing

The library includes a comprehensive test suite with 92 tests covering all functionality:

```bash
# Run all tests
composer test

# View test results with detailed output
vendor/bin/phpunit -c tests/phpunit.xml --testdox
```

## Configuration Reference

### HandlerConfig Options

```php
use WebFiori\Error\Config\HandlerConfig;

$config = new HandlerConfig();

// Error reporting settings
$config->setErrorReporting(E_ALL);
$config->setDisplayErrors(false);
$config->setDisplayStartupErrors(false);

// Global settings modification
$config->setModifyGlobalSettings(true);
$config->setRespectExistingSettings(false);

// Apply the configuration
$config->apply();
```

### SecurityConfig Options

```php
use WebFiori\Error\Security\SecurityConfig;

$security = new SecurityConfig('production');

// Control what information is shown
$security->shouldShowStackTrace(); // Returns bool
$security->shouldShowLineNumbers(); // Returns bool
$security->shouldShowFullPaths(); // Returns bool

// Check security level
$security->isProduction(); // Returns bool
$security->isDevelopment(); // Returns bool
$security->isStaging(); // Returns bool
```

## Best Practices

### Environment-Specific Handlers

```php
// Register different handlers for different environments
if ($_ENV['APP_ENV'] === 'production') {
    Handler::registerHandler(new ProductionHandler());
} else {
    Handler::registerHandler(new DevelopmentHandler());
}

// Always have a fallback
Handler::registerHandler(new DefaultHandler(), 1);
```

### Logging Strategy

```php
class LoggingHandler extends AbstractHandler {
    public function handle(): void {
        // Always log errors
        $this->secureLog('Error occurred', [
            'environment' => $_ENV['APP_ENV'],
            'timestamp' => time(),
            'memory_usage' => memory_get_usage(true)
        ]);
        
        // Don't output in production
        if ($_ENV['APP_ENV'] !== 'production') {
            $this->displayError();
        }
    }
    
    private function displayError(): void {
        // Display error using secureOutput
        $this->secureOutput("Error: " . $this->getMessage());
    }
}
```

### Security Considerations

```php
// Never expose sensitive information
class SecureHandler extends AbstractHandler {
    public function handle(): void {
        // Use built-in sanitization
        $sanitizedMessage = $this->getMessage(); // Already sanitized
        
        // Log security violations
        if ($this->isSensitiveError()) {
            $this->logSecurityViolation('Sensitive error detected');
        }
        
        // Show generic message in production
        if ($this->createSecurityConfig()->isProduction()) {
            echo 'An error occurred. Please contact support.';
        }
    }
    
    private function isSensitiveError(): bool {
        // Your security detection logic
        return false;
    }
}
```

## Troubleshooting

### Common Issues

1. **Handler not triggering**: Ensure handlers are registered with `Handler::registerHandler()`
2. **Memory issues**: Enable memory management in configuration
3. **Infinite loops**: Enable infinite loop protection with `Handler::setMaxHandlerExecutions()`
4. **Missing output**: Check if handler is active and has correct priority

### Debug Mode

```php
use WebFiori\Error\Config\HandlerConfig;
use WebFiori\Error\Handler;

// Enable debug mode for troubleshooting
$config = HandlerConfig::createDevelopmentConfig();
Handler::setConfig($config);
```

## License

This library is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please read our contributing guidelines and submit pull requests to our GitHub repository.

## Support

- **Issues**: [GitHub Issues](https://github.com/WebFiori/err/issues)
