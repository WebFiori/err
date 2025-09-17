# CLI vs HTTP Output Example

This example demonstrates how the WebFiori Error Handler automatically adapts its output format based on the execution context (CLI vs HTTP).

## What This Example Shows

- Automatic detection of CLI vs HTTP environment
- Different output formatting for each context
- ANSI color support in CLI mode
- HTML formatting for HTTP responses
- Context-aware error display

## Key Concepts

1. **Environment Detection**: Automatic CLI/HTTP detection
2. **Output Formatting**: Different formats for different contexts
3. **ANSI Colors**: Terminal color support for CLI
4. **HTML Output**: Structured HTML for web browsers
5. **Responsive Design**: Adapting to the execution environment

## Files

- `example.php` - Main example showing context detection

## Running the Example

```bash
# Run in CLI mode
php example.php

# Run HTTP mode via built-in server
php -S localhost:8000
# Then access: http://localhost:8000/example.php
```

## Expected Output

- **CLI**: Plain text with ANSI colors and formatting
- **HTTP**: HTML with CSS styling and structured layout

## Note

The DefaultHandler includes output buffering fixes to ensure proper HTTP response delivery in web server environments.
