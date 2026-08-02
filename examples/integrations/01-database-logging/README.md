# Database Logging Example

This example demonstrates how to log errors to a database using a custom handler.

## What This Example Shows

- Creating a database logging handler
- Setting up database schema for error logging
- Storing structured error information
- Querying and analyzing logged errors
- Database connection management in handlers

## Key Concepts

1. **Database Handler**: Custom handler for database logging
2. **Error Schema**: Database structure for storing error information
3. **Structured Logging**: Storing errors in queryable format
4. **Connection Management**: Handling database connections safely
5. **Error Analysis**: Querying logged errors for insights

## Files

- `example.php` - Main database logging demonstration
- `setup-database.php` - Database schema setup
- `query-errors.php` - Querying and analyzing logged errors

## Requirements

- SQLite (included with PHP) or other PDO-supported database
- Write permissions for database file creation

## Running the Example

```bash
# Setup database schema
php setup-database.php

# Run the main example
php example.php

# Query logged errors
php query-errors.php
```

## Expected Output

You'll see errors being logged to a database and then queried for analysis and reporting.
