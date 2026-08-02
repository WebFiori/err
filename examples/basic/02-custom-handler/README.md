# Custom Handler Example

This example demonstrates how to create and register custom error handlers.

## What This Example Shows

- Creating a custom handler by extending `AbstractHandler`
- Implementing required methods (`handle`, `isActive`, `isShutdownHandler`)
- Registering custom handlers with the system
- Accessing error information in handlers
- Custom output formatting

## Key Concepts

1. **AbstractHandler**: Base class for all custom handlers
2. **Handler Methods**: Required methods that define handler behavior
3. **Error Information**: Accessing sanitized error data
4. **Handler Registration**: Adding handlers to the system
5. **Handler Priority**: Controlling execution order

## Files

- `example.php` - Main example with custom handler implementation
- `custom-handlers.php` - Additional custom handler examples

## Running the Example

```bash
php example.php
```

## Expected Output

You'll see custom-formatted error output from your handler, demonstrating how you can control the appearance and content of error messages.
