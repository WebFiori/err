<?php
/**
 * Database Setup Script
 * 
 * This script creates the database schema for error logging.
 */

$dbFile = __DIR__ . '/errors.db';

try {
    $pdo = new PDO("sqlite:{$dbFile}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Setting up error logging database...\n";
    
    // Create error_logs table
    $createTable = "
        CREATE TABLE IF NOT EXISTS error_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            exception_type VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            file VARCHAR(500) NOT NULL,
            line INTEGER NOT NULL,
            trace TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            severity VARCHAR(50) DEFAULT 'error',
            user_agent TEXT,
            request_uri TEXT,
            remote_addr VARCHAR(45),
            session_id VARCHAR(255),
            memory_usage INTEGER,
            execution_time REAL
        )
    ";
    
    $pdo->exec($createTable);
    
    // Create indexes for better query performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_error_type ON error_logs(exception_type)",
        "CREATE INDEX IF NOT EXISTS idx_created_at ON error_logs(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_severity ON error_logs(severity)",
        "CREATE INDEX IF NOT EXISTS idx_file_line ON error_logs(file, line)"
    ];
    
    foreach ($indexes as $index) {
        $pdo->exec($index);
    }
    
    echo "✓ Created error_logs table\n";
    echo "✓ Created performance indexes\n";
    echo "✓ Database setup completed successfully!\n";
    echo "\nDatabase file: {$dbFile}\n";
    
} catch (PDOException $e) {
    echo "Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
