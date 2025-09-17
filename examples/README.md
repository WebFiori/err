# WebFiori Error Handler - Usage Examples

This directory contains comprehensive examples demonstrating various features and use cases of the WebFiori Error Handler library.

## Example Categories

### 📚 Basic Examples (`/basic`)
- **[01-simple-usage](basic/01-simple-usage)** - Getting started with the default handler
- **[02-custom-handler](basic/02-custom-handler)** - Creating and registering custom handlers
- **[03-multiple-handlers](basic/03-multiple-handlers)** - Using multiple handlers with priorities
- **[04-configuration](basic/04-configuration)** - Basic configuration options
- **[05-cli-vs-http](basic/05-cli-vs-http)** - Different output for CLI and HTTP contexts

### 🚀 Advanced Examples (`/advanced`)
- **[01-handler-priorities](advanced/01-handler-priorities)** - Advanced handler priority management
- **[02-shutdown-handlers](advanced/02-shutdown-handlers)** - Handling fatal errors and shutdown
- **[03-memory-management](advanced/03-memory-management)** - Memory optimization and cleanup

### 🔌 Integration Examples (`/integrations`)
- **[01-database-logging](integrations/01-database-logging)** - Logging errors to database
- **[02-file-logging](integrations/02-file-logging)** - Advanced file-based logging

### 🔒 Security Examples (`/security`)
- **[01-output-sanitization](security/01-output-sanitization)** - Sanitizing sensitive information
- **[02-production-security](security/02-production-security)** - Production security configuration

## Running Examples

Each example is self-contained and can be run independently:

```bash
# Run a specific example
php examples/basic/01-simple-usage/example.php

# Run with different error scenarios
php examples/basic/01-simple-usage/example.php --trigger-error
```

## Requirements

- PHP 8.1 or higher
- WebFiori Error Handler library installed via Composer
- Some examples may require additional dependencies (documented in individual READMEs)

## Example Structure


## Getting Help

- Check individual example READMEs for detailed explanations
- Review the main library documentation
- Look at the test files for additional usage patterns
