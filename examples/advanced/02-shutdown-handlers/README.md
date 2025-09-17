# Shutdown Handlers Example

This example demonstrates how to handle fatal errors and shutdown scenarios using shutdown handlers.

## What This Example Shows

- Implementing shutdown handlers for fatal errors
- Difference between regular and shutdown handlers
- Handling memory exhaustion and fatal errors
- Cleanup operations during shutdown
- Graceful error handling in critical situations

## Key Concepts

1. **Shutdown Handlers**: Special handlers for fatal errors and shutdown
2. **Fatal Error Handling**: Catching errors that would normally terminate the script
3. **Memory Management**: Handling memory-related fatal errors
4. **Cleanup Operations**: Performing cleanup during shutdown
5. **Error Recovery**: Attempting recovery from fatal conditions

## Files

- `example.php` - Main shutdown handler demonstration

## Running the Example

```bash
php example.php
```

## Expected Output

You'll see how shutdown handlers activate during fatal error conditions and perform cleanup operations.
