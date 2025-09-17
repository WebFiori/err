# Output Sanitization Example

This example demonstrates how the WebFiori Error Handler automatically sanitizes sensitive information in error output.

## What This Example Shows

- Automatic sanitization of sensitive data in error messages
- Protection against information disclosure
- Sanitization of file paths and stack traces
- Security-aware error display
- Different sanitization levels for different environments

## Key Concepts

1. **Output Sanitization**: Automatic cleaning of sensitive information
2. **Information Disclosure Prevention**: Protecting sensitive data
3. **Path Sanitization**: Hiding internal file structure
4. **Environment-Aware Security**: Different levels based on environment
5. **Security Configuration**: Controlling sanitization behavior

## Files

- `example-01-sensitive-messages.php` - Sensitive information in exception messages
- `example-02-path-sanitization.php` - File path sanitization by security level
- `example-03-sql-injection-sanitization.php` - SQL injection and XSS sanitization
- `example-04-custom-sanitization.php` - Custom sanitization handler implementation

## Running the Examples

```bash
# Run individual examples
php example-01-sensitive-messages.php
php example-02-path-sanitization.php
php example-03-sql-injection-sanitization.php
php example-04-custom-sanitization.php

```

## Expected Output

You'll see how sensitive information is automatically sanitized in error output while maintaining useful debugging information.
