# Configuration Examples

This directory demonstrates how to configure the WebFiori Error Handler system using different approaches and environments.

## What These Examples Show

- Using `HandlerConfig` to configure error handling behavior
- Setting error reporting levels for different environments
- Controlling error display options
- Pre-built configuration methods
- Custom configuration settings
- Automatic security level updates

## Key Concepts

1. **HandlerConfig**: Main configuration class for the error handler
2. **Error Reporting Levels**: Controlling which errors are processed (security layer 1)
3. **Display Options**: Controlling what information is shown
4. **Security Levels**: Automatic security configuration based on display settings (security layer 2)
5. **Environment-Specific Configs**: Pre-built configurations for different environments

## Files

- `example1-basic.php` - Basic manual configuration with custom error reporting
- `example2-production.php` - Production-safe configuration using `createProductionConfig()`
- `example3-development.php` - Development configuration using `createDevelopmentConfig()`
- `example4-custom.php` - Custom configuration with global settings control

## Running the Examples

```bash
php example1-basic.php
php example2-production.php
php example3-development.php
php example4-custom.php
```

## Expected Output

Each example shows:
- Configuration settings being applied
- How different error reporting levels affect behavior
- Automatic security level updates based on display settings
- Exception handling with the configured settings
