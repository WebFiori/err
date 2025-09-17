<?php
/**
 * Error Analysis Script
 * 
 * This script queries and analyzes logged errors from the database.
 */

$dbFile = __DIR__ . '/errors.db';

if (!file_exists($dbFile)) {
    echo "Database not found. Please run setup-database.php and example.php first.\n";
    exit(1);
}

try {
    $pdo = new PDO("sqlite:{$dbFile}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "WebFiori Error Handler - Error Analysis\n";
echo str_repeat('=', 40) . "\n\n";

// 1. Error Summary
echo "1. ERROR SUMMARY\n";
echo str_repeat('-', 20) . "\n";

$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_errors,
        COUNT(DISTINCT exception_type) as unique_types,
        MIN(created_at) as first_error,
        MAX(created_at) as last_error
    FROM error_logs
");

$summary = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Total Errors: {$summary['total_errors']}\n";
echo "Unique Types: {$summary['unique_types']}\n";
echo "First Error: {$summary['first_error']}\n";
echo "Last Error: {$summary['last_error']}\n\n";

// 2. Errors by Type
echo "2. ERRORS BY TYPE\n";
echo str_repeat('-', 20) . "\n";

$stmt = $pdo->query("
    SELECT 
        exception_type,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM error_logs), 2) as percentage
    FROM error_logs 
    GROUP BY exception_type 
    ORDER BY count DESC
");

$errorTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($errorTypes as $type) {
    echo sprintf(
        "%-25s: %3d (%5.1f%%)\n",
        $type['exception_type'],
        $type['count'],
        $type['percentage']
    );
}

echo "\n";

// 3. Errors by Severity
echo "3. ERRORS BY SEVERITY\n";
echo str_repeat('-', 22) . "\n";

$stmt = $pdo->query("
    SELECT 
        severity,
        COUNT(*) as count
    FROM error_logs 
    GROUP BY severity 
    ORDER BY 
        CASE severity 
            WHEN 'fatal' THEN 1 
            WHEN 'error' THEN 2 
            WHEN 'warning' THEN 3 
            ELSE 4 
        END
");

$severities = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($severities as $severity) {
    $bar = str_repeat('█', min(20, $severity['count']));
    echo sprintf("%-10s: %3d %s\n", $severity['severity'], $severity['count'], $bar);
}

echo "\n";

// 4. Most Common Error Messages
echo "4. MOST COMMON ERROR MESSAGES\n";
echo str_repeat('-', 32) . "\n";

$stmt = $pdo->query("
    SELECT 
        message,
        COUNT(*) as count
    FROM error_logs 
    GROUP BY message 
    ORDER BY count DESC 
    LIMIT 5
");

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $i => $msg) {
    echo ($i + 1) . ". " . substr($msg['message'], 0, 50);
    if (strlen($msg['message']) > 50) echo "...";
    echo " ({$msg['count']} times)\n";
}

echo "\n";

// 5. Error Timeline (last 24 hours)
echo "5. ERROR TIMELINE (Recent Activity)\n";
echo str_repeat('-', 36) . "\n";

$stmt = $pdo->query("
    SELECT 
        strftime('%H:00', created_at) as hour,
        COUNT(*) as count
    FROM error_logs 
    WHERE created_at >= datetime('now', '-24 hours')
    GROUP BY strftime('%H:00', created_at)
    ORDER BY hour
");

$timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($timeline)) {
    echo "No errors in the last 24 hours.\n";
} else {
    foreach ($timeline as $hour) {
        $bar = str_repeat('▓', min(30, $hour['count']));
        echo sprintf("%5s: %3d %s\n", $hour['hour'], $hour['count'], $bar);
    }
}

echo "\n";

// 6. Performance Impact
echo "6. PERFORMANCE IMPACT\n";
echo str_repeat('-', 22) . "\n";

$stmt = $pdo->query("
    SELECT 
        AVG(memory_usage) as avg_memory,
        MAX(memory_usage) as max_memory,
        AVG(execution_time) as avg_time,
        MAX(execution_time) as max_time
    FROM error_logs 
    WHERE memory_usage IS NOT NULL
");

$performance = $stmt->fetch(PDO::FETCH_ASSOC);

if ($performance['avg_memory']) {
    echo "Average Memory Usage: " . formatBytes($performance['avg_memory']) . "\n";
    echo "Peak Memory Usage: " . formatBytes($performance['max_memory']) . "\n";
    echo "Average Execution Time: " . round($performance['avg_time'] * 1000, 2) . " ms\n";
    echo "Max Execution Time: " . round($performance['max_time'] * 1000, 2) . " ms\n";
} else {
    echo "No performance data available.\n";
}

echo "\n";

// 7. Recent Critical Errors
echo "7. RECENT CRITICAL ERRORS\n";
echo str_repeat('-', 26) . "\n";

$stmt = $pdo->query("
    SELECT 
        id,
        exception_type,
        message,
        created_at
    FROM error_logs 
    WHERE severity = 'fatal'
    ORDER BY created_at DESC 
    LIMIT 5
");

$criticalErrors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($criticalErrors)) {
    echo "No critical errors found.\n";
} else {
    foreach ($criticalErrors as $error) {
        echo sprintf(
            "[%d] %s: %s\n    Time: %s\n",
            $error['id'],
            $error['exception_type'],
            substr($error['message'], 0, 60) . (strlen($error['message']) > 60 ? '...' : ''),
            $error['created_at']
        );
        echo "\n";
    }
}

echo "Error analysis completed!\n";

function formatBytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
