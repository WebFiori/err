# Simple Usage Example

This example demonstrates the most basic usage of the WebFiori Error Handler library.

## What This Example Shows

- How to include the library in your project
- Automatic error handler registration
- Basic error catching and display
- Default handler behavior

## Key Concepts

1. **Automatic Registration**: The handler system starts automatically when first accessed
2. **Default Handler**: Provides sensible defaults for error display
3. **Error Conversion**: PHP errors are converted to exceptions for consistent handling
4. **Environment Detection**: Automatically detects CLI vs HTTP context

## Files

- `example.php` - Main example demonstrating basic usage
- `trigger-errors.php` - Script to trigger different types of errors for testing

## Running the Example

```bash
# Basic usage
php example.php

# Trigger different error types
php trigger-errors.php
```

## Expected Output

The example will show formatted error output appropriate for your environment (CLI or HTTP).
In development mode, you'll see detailed error information including stack traces.
