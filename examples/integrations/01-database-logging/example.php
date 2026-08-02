<?php
/**
 * Database Logging Example
 * 
 * This example demonstrates logging errors to a database.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\Handler;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Config\HandlerConfig;

// Set environment to development to avoid security violations
Handler::setConfig(HandlerConfig::createDevelopmentConfig());

/**
 * Database logging handler
 */
class DatabaseLogHandler extends AbstractHandler {
    private PDO $pdo;
    private float $startTime;
    
    public function __construct(PDO $pdo) {
        parent::__construct();
        $this->pdo = $pdo;
        $this->setName('DatabaseLog');
        $this->startTime = microtime(true);
    }
    
    public function handle(): void {
        try {
            $executionTime = microtime(true) - $this->startTime;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO error_logs (
                    exception_type, message, file, line, trace, severity,
                    user_agent, request_uri, remote_addr, session_id,
                    memory_usage, execution_time
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $trace = json_encode(array_map(function($entry) {
                return (string)$entry;
            }, $this->getTrace()));
            
            $stmt->execute([
                get_class($this->getException()),
                $this->getMessage(),
                $this->getFile(),
                $this->getLine(),
                $trace,
                $this->getErrorSeverity(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REQUEST_URI'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                session_id() ?: null,
                memory_get_usage(true),
                $executionTime
            ]);
            
            $errorId = $this->pdo->lastInsertId();
            echo "✓ Error logged to database with ID: {$errorId}\n";
            
        } catch (PDOException $e) {
            echo "✗ Failed to log error to database: " . $e->getMessage() . "\n";
        }
    }
    
    public function isActive(): bool {
        return true;
    }
    
    public function isShutdownHandler(): bool {
        return true;
    }
    
    private function getErrorSeverity(): string {
        $exception = $this->getException();
        
        if ($exception instanceof Error) {
            return 'fatal';
        }
        if ($exception instanceof RuntimeException) {
            return 'error';
        }
        if ($exception instanceof InvalidArgumentException) {
            return 'warning';
        }
        
        return 'error';
    }
}

/**
 * Console display handler for immediate feedback
 */
class ConsoleDisplayHandler extends AbstractHandler {
    
    public function __construct() {
        parent::__construct();
        $this->setName('ConsoleDisplay');
        $this->setPriority(100); // Higher priority for immediate display
    }
    
    public function handle(): void {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "ERROR DETECTED\n";
        echo str_repeat('=', 50) . "\n";
        echo "Type: " . get_class($this->getException()) . "\n";
        echo "Message: " . $this->getMessage() . "\n";
        echo "Location: " . $this->getClass() . ":" . $this->getLine() . "\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('=', 50) . "\n";
    }
    
    public function isActive(): bool {
        return true;
    }
    
    public function isShutdownHandler(): bool {
        return false;
    }
}

echo "WebFiori Error Handler - Database Logging Example\n";
echo str_repeat('=', 55) . "\n\n";

// Setup database connection
$dbFile = __DIR__ . '/errors.db';

if (!file_exists($dbFile)) {
    echo "Database not found. Please run setup-database.php first.\n";
    exit(1);
}

try {
    $pdo = new PDO("sqlite:{$dbFile}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database: {$dbFile}\n\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Register handlers
Handler::registerHandler(new ConsoleDisplayHandler());
Handler::registerHandler(new DatabaseLogHandler($pdo));

echo "Registered handlers:\n";
echo "- ConsoleDisplayHandler (immediate display)\n";
echo "- DatabaseLogHandler (database logging)\n\n";

// Simulate different types of errors
$testErrors = [
    ['type' => 'RuntimeException', 'message' => 'Database connection timeout'],
    ['type' => 'InvalidArgumentException', 'message' => 'Invalid user ID provided'],
    ['type' => 'Error', 'message' => 'Fatal memory allocation error'],
    ['type' => 'Exception', 'message' => 'General application error'],
    ['type' => 'LogicException', 'message' => 'Invalid application state']
];

echo "Triggering test errors for database logging...\n";

foreach ($testErrors as $i => $errorInfo) {
    echo "\nTest Error " . ($i + 1) . ": {$errorInfo['type']}\n";
    echo str_repeat('-', 40) . "\n";
    
    try {
        $exceptionClass = $errorInfo['type'];
        throw new $exceptionClass($errorInfo['message']);
    } catch (Throwable $e) {
        Handler::handleException($e);
    }
    
    // Small delay to ensure different timestamps
    usleep(100000); // 0.1 second
}

// Query recent errors from database
echo "\n" . str_repeat('=', 55) . "\n";
echo "RECENT ERRORS FROM DATABASE\n";
echo str_repeat('=', 55) . "\n";

try {
    $stmt = $pdo->prepare("
        SELECT id, exception_type, message, created_at, severity 
        FROM error_logs 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    
    $errors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($errors)) {
        echo "No errors found in database.\n";
    } else {
        foreach ($errors as $error) {
            echo sprintf(
                "[%d] %s: %s (%s) - %s\n",
                $error['id'],
                $error['exception_type'],
                $error['message'],
                $error['severity'],
                $error['created_at']
            );
        }
    }
    
} catch (PDOException $e) {
    echo "Failed to query errors: " . $e->getMessage() . "\n";
}

echo "\nDatabase logging example completed!\n";
echo "Run query-errors.php to see more detailed analysis.\n";
