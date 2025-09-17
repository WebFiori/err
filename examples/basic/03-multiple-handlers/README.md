# Multiple Handlers Example

This example demonstrates how to use multiple error handlers with different priorities and purposes.

## What This Example Shows

- Registering multiple handlers in the same application
- Handler priority system and execution order
- Different handlers for different purposes (logging, display, notification)
- Handler activation conditions
- Combining handlers for comprehensive error handling

## Key Concepts

1. **Handler Priority**: Higher priority handlers execute first
2. **Handler Chaining**: Multiple handlers can process the same error
3. **Conditional Activation**: Handlers can be active based on conditions
4. **Specialized Handlers**: Different handlers for different purposes
5. **Handler Coordination**: How handlers work together

## Files

- `example.php` - Main example with multiple handlers

## Running the Example

```bash
php example.php
```

## Expected Output

You'll see output from multiple handlers processing the same error, demonstrating how they work together to provide comprehensive error handling.
