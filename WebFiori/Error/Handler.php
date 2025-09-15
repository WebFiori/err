<?php
namespace WebFiori\Error;

use Exception;
use Throwable;
use WebFiori\Error\Config\HandlerConfig;
/**
 * The core class which is used to define errors and exceptions handling.
 * 
 * This class provides a centralized error and exception handling system that:
 * - Converts PHP errors to exceptions for consistent handling
 * - Manages multiple exception handlers with priority-based execution
 * - Supports both normal and shutdown handlers
 * - Provides a singleton pattern for global access
 * 
 * Usage Example:
 * ```php
 * // Register a custom handler
 * Handler::registerHandler(new MyCustomHandler());
 * 
 * // The handler will automatically catch and process errors/exceptions
 * $undefinedVariable; // This will be caught and handled
 * ```
 *
 * @author Ibrahim
 */
class Handler {
    /**
     * An array which holds constants that define the meanings of different PHP errors.
     * 
     * This mapping is used when converting PHP errors to exceptions to provide
     * meaningful error descriptions. Each error type includes:
     * - 'type': The PHP error constant name
     * - 'description': Human-readable description of the error
     * 
     * @var array<int, array{type: string, description: string}>
     */
    const ERR_TYPES = [
        E_ERROR => [
            'type' => 'E_ERROR',
            'description' => 'Fatal run-time error'
        ],
        E_WARNING => [
            'type' => 'E_WARNING',
            'description' => 'Run-time warning'
        ],
        E_PARSE => [
            'type' => 'E_PARSE',
            'description' => 'Compile-time parse error'
        ],
        E_NOTICE => [
            'type' => 'E_NOTICE',
            'description' => 'Run-time notice'
        ],
        E_CORE_ERROR => [
            'type' => 'E_CORE_ERROR',
            'description' => 'Fatal error during initialization'
        ],
        E_CORE_WARNING => [
            'type' => 'E_CORE_WARNING',
            'description' => 'Warning during initialization'
        ],
        E_COMPILE_ERROR => [
            'type' => 'E_COMPILE_ERROR',
            'description' => 'Fatal compile-time error'
        ],
        E_COMPILE_WARNING => [
            'type' => 'E_COMPILE_WARNING',
            'description' => 'Compile-time warning'
        ],
        E_USER_ERROR => [
            'type' => 'E_USER_ERROR',
            'description' => 'User-generated error message'
        ],
        E_USER_WARNING => [
            'type' => 'E_USER_WARNING',
            'description' => 'User-generated warning message'
        ],
        E_USER_NOTICE => [
            'type' => 'E_USER_NOTICE',
            'description' => 'User-generated notice message'
        ],
        //The deprecated E_STRICT
        2048 => [
            'type' => 'E_STRICT',
            'description' => 'PHP suggest a change'
        ],
        E_RECOVERABLE_ERROR => [
            'type' => 'E_RECOVERABLE_ERROR',
            'description' => 'Catchable fatal error'
        ],
        E_DEPRECATED => [
            'type' => 'E_DEPRECATED',
            'description' => 'Run-time notice'
        ],
        E_USER_DEPRECATED => [
            'type' => 'E_USER_DEPRECATED',
            'description' => 'User-generated warning message'
        ],
    ];
    
    /**
     * @var array<AbstractHandler>
     */
    private array $handlersPool;
    
    /**
     * @var Handler|null
     */
    private static ?Handler $inst = null;
    
    /**
     * @var array<string, int> Track handler execution count to prevent infinite loops
     */
    private static array $handlerExecutionCount = [];
    
    /**
     * @var int Maximum number of times a handler can be executed in a single request
     */
    private static int $maxHandlerExecutions = 3;
    
    /**
     * @var bool Flag to prevent recursive handler execution
     */
    private static bool $isHandlingException = false;
    
    /**
     * @var array<string, WeakReference> Weak references to handlers to prevent memory leaks
     */
    private static array $handlerWeakRefs = [];
    
    /**
     * @var int Memory usage threshold for cleanup (in bytes)
     */
    private static int $memoryThreshold = 50 * 1024 * 1024; // 50MB
    
    /**
     * @var \WebFiori\Error\Config\HandlerConfig|null Configuration instance
     */
    private static ?\WebFiori\Error\Config\HandlerConfig $config = null;
    
    /**
     * @var bool
     */
    private bool $isErrOccured;
    
    /**
     * @var Throwable|null
     */
    private ?Throwable $lastException = null;
    
    /**
     * @var callable
     */
    private $errToExceptionHandler;
    
    /**
     * @var callable
     */
    private $exceptionsHandler;
    
    /**
     * @var callable
     */
    private $shutdownFunction;
    
    /**
     * Private constructor to enforce singleton pattern.
     * 
     * Initializes the error handling system by:
     * - Setting up PHP error reporting
     * - Creating error and exception handlers
     * - Registering handlers with PHP
     * - Adding the default handler
     */
    private function __construct() {
        $this->initializeConfiguration();
        $this->createHandlers();
        $this->registerPhpHandlers();
        $this->initializeHandlerPool();
    }
    
    /**
     * Initialize configuration system.
     * 
     * This method sets up the configuration without modifying global PHP settings
     * unless explicitly configured to do so.
     */
    private function initializeConfiguration(): void {
        // Use existing config or create default
        if (self::$config === null) {
            self::$config = new \WebFiori\Error\Config\HandlerConfig();
        }
        
        // Apply configuration (respects modifyGlobalSettings flag)
        self::$config->apply();
        
        $this->isErrOccured = false;
    }
    
    /**
     * Create the error and exception handler functions.
     */
    private function createHandlers(): void {
        $this->createErrorToExceptionHandler();
        $this->createExceptionsHandler();
        $this->createShutdownHandler();
    }
    
    /**
     * Create the error-to-exception conversion handler.
     */
    private function createErrorToExceptionHandler(): void {
        $this->errToExceptionHandler = function (int $errno, string $errString, string $errFile, int $errLine): void {
            $errClass = TraceEntry::extractClassName($errFile);
            $errType = self::ERR_TYPES[$errno] ?? ['type' => 'UNKNOWN', 'description' => 'Unknown error'];
            $message = sprintf(
                'An exception caused by an error. %s: %s at %s Line %d',
                $errType['description'],
                $errString,
                $errClass,
                $errLine
            );
            throw new ErrorHandlerException($message, $errno, $errFile, $errLine);
        };
    }
    
    /**
     * Create the main exceptions handler.
     */
    private function createExceptionsHandler(): void {
        $this->exceptionsHandler = function (?Throwable $ex = null): void {
            $instance = self::get();
            $instance->lastException = $ex;
            $instance->sortHandlers();
            
            foreach ($instance->handlersPool as $handler) {
                if ($handler->isActive() && !$handler->isShutdownHandler()) {
                    $this->executeHandler($handler, $ex);
                }
            }
        };
    }
    
    /**
     * Create the shutdown handler for handling errors after script execution.
     */
    private function createShutdownHandler(): void {
        $this->shutdownFunction = function (): void {
            $instance = self::get();
            $lastException = $instance->lastException;
            
            if ($lastException === null) {
                return;
            }
            
            $this->cleanOutputBuffer();
            
            foreach ($instance->handlersPool as $handler) {
                if ($this->shouldExecuteShutdownHandler($handler)) {
                    $this->executeHandler($handler, $lastException);
                }
            }
        };
    }
    
    /**
     * Execute a single handler with proper state management and infinite loop protection.
     * 
     * @param AbstractHandler $handler The handler to execute
     * @param Throwable|null $exception The exception to handle
     */
    private function executeHandler(AbstractHandler $handler, ?Throwable $exception): void {
        $handlerName = $handler->getName();
        
        // Check for infinite loop protection
        if (self::$isHandlingException) {
            error_log("Handler execution blocked: Already handling an exception to prevent infinite loop");
            return;
        }
        
        // Check execution count limit
        if (!isset(self::$handlerExecutionCount[$handlerName])) {
            self::$handlerExecutionCount[$handlerName] = 0;
        }
        
        if (self::$handlerExecutionCount[$handlerName] >= self::$maxHandlerExecutions) {
            error_log("Handler '{$handlerName}' execution blocked: Maximum execution limit (" . self::$maxHandlerExecutions . ") reached");
            return;
        }
        
        // Increment execution count
        self::$handlerExecutionCount[$handlerName]++;
        
        if ($exception instanceof Throwable) {
            $handler->setException($exception);
        }
        
        $handler->setIsExecuting(true);
        
        // Set flag to prevent recursive execution
        self::$isHandlingException = true;
        
        try {
            // Execute the handler
            $handler->handle();
            
        } catch (Throwable $handlerException) {
            // Log handler failures
            $this->logHandlerFailure($handler, $handlerException);
            
            // Fallback to default behavior (but don't trigger another handler)
            $this->handleFailureFallback($handlerException);
            
        } finally {
            $handler->setIsExecuting(false);
            $handler->setIsExecuted(true);
            
            // Reset flag to allow future handler execution
            self::$isHandlingException = false;
        }
    }
    
    /**
     * Clean the output buffer if it contains data.
     */
    private function cleanOutputBuffer(): void {
        if (ob_get_length() > 0) {
            ob_clean();
        }
    }
    
    /**
     * Check if a shutdown handler should be executed.
     * 
     * @param AbstractHandler $handler The handler to check
     * @return bool True if the handler should be executed
     */
    private function shouldExecuteShutdownHandler(AbstractHandler $handler): bool {
        return $handler->isActive() 
            && $handler->isShutdownHandler() 
            && !$handler->isExecuted() 
            && !$handler->isExecuting();
    }
    
    /**
     * Register handlers with PHP's error handling system.
     */
    private function registerPhpHandlers(): void {
        set_error_handler($this->errToExceptionHandler);
        set_exception_handler($this->exceptionsHandler);
        register_shutdown_function($this->shutdownFunction);
    }
    
    /**
     * Initialize the handler pool with the default handler.
     */
    private function initializeHandlerPool(): void {
        $this->handlersPool = [];
        $this->handlersPool[] = new DefaultHandler();
    }
    
    /**
     * Invoke the exceptions handler for testing purposes.
     * 
     * @param Throwable|null $ex The exception to handle
     */
    public function invokeExceptionsHandler(?Throwable $ex = null): void {
        self::get()->sortHandlers();
        self::get()->lastException = $ex;
        call_user_func(self::get()->exceptionsHandler, $ex);
    }
    
    /**
     * Invoke the shutdown handler for testing purposes.
     */
    public function invokeShutdownHandler(): void {
        // Create a test exception if none exists
        if (self::get()->lastException === null) {
            self::get()->lastException = new Exception('Test exception for shutdown handler');
        }
        call_user_func(self::get()->shutdownFunction);
    }
    
    /**
     * Sort all registered handlers based on their priority.
     * 
     * The ones with higher priority will come first.
     */
    public function sortHandlers(): void {
        $customSortFunc = function (AbstractHandler $first, AbstractHandler $second): int {
            return $second->getPriority() - $first->getPriority();
        };
        usort($this->handlersPool, $customSortFunc);
    }
    
    /**
     * Reset handler status to default.
     * 
     * This will remove all registered handlers and only add the default one.
     */ 
    public static function reset(): void {
        $h = self::get();
        $h->handlersPool = [];
        $h->handlersPool[] = new DefaultHandler();
        $h->lastException = null;
        set_error_handler($h->errToExceptionHandler);
        
        // Reset infinite loop protection
        self::$handlerExecutionCount = [];
        self::$isHandlingException = false;
        
        // Re-apply current configuration (don't reset it)
        if (self::$config !== null) {
            self::$config->apply();
        }
    }
    
    /**
     * Returns a handler given its name.
     * 
     * @param string $name The name of the handler.
     * 
     * @return AbstractHandler|null If a handler which has the given name is found,
     * it will be returned as an object. Other than that, null is returned.
     */
    public static function &getHandler(string $name): ?AbstractHandler {
        $h = null;
        $trimmed = trim($name);

        foreach (self::get()->handlersPool as $handler) {
            if ($handler->getName() === $trimmed) {
                $h = $handler;
                break;
            }
        }

        return $h;
    }
    
    /**
     * Returns an array that contains all registered handlers as objects.
     * 
     * @return array<AbstractHandler>
     */
    public static function getHandlers(): array {
        return self::get()->handlersPool;
    }
    
    /**
     * Returns the instance which is used to handle exceptions and errors.
     * 
     * @return Handler An instance of the class.
     */
    public static function get(): Handler {
        if (self::$inst === null) {
            self::$inst = new Handler();
        }

        return self::$inst;
    }
    
    /**
     * Checks if a handler is registered or not given its name.
     * 
     * @param string $name The name of the handler.
     * 
     * @return bool If such handler is registered, the method will return true.
     * Other than that, the method will return false.
     */
    public static function hasHandler(string $name): bool {
        $trimmed = trim($name);

        foreach (self::get()->handlersPool as $handler) {
            if ($handler->getName() === $trimmed) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Registers a custom handler to handle exceptions and errors.
     * 
     * The handler will be added to the handler pool if no handler with the same
     * name already exists. Handlers are executed based on their priority,
     * with higher priority handlers executing first.
     * 
     * Example:
     * ```php
     * $handler = new MyCustomHandler();
     * $handler->setPriority(100);
     * Handler::registerHandler($handler);
     * ```
     * 
     * @param AbstractHandler $h A class that implements a custom handler.
     * 
     * @throws InvalidArgumentException If handler name is empty or invalid
     * 
     * @see AbstractHandler For implementing custom handlers
     * @see unregisterHandler() For removing handlers
     */
    public static function registerHandler(AbstractHandler $h): void {
        if (!self::hasHandler($h->getName())) {
            self::get()->handlersPool[] = $h;
        }
    }
    
    /**
     * Remove a registered errors handler using its name or class name.
     * 
     * This method can remove handlers by:
     * 1. Handler name (as set by setName())
     * 2. Full class name (using ClassName::class syntax)
     * 
     * Example:
     * ```php
     * // Remove by name
     * Handler::unregisterHandlerByName('MyHandler');
     * 
     * // Remove by class name
     * Handler::unregisterHandlerByName(MyCustomHandler::class);
     * ```
     * 
     * @param string $identifier The name or class name of the handler to remove
     * 
     * @return bool True if a handler was removed, false otherwise
     */
    public static function unregisterHandlerByName(string $identifier): bool {
        $trimmedIdentifier = trim($identifier);
        
        // First, try to find by handler name
        $handler = self::getHandler($trimmedIdentifier);
        if ($handler !== null) {
            return self::unregisterHandler($handler);
        }
        
        // If not found by name, try to find by class name
        return self::unregisterHandlerByClassName($trimmedIdentifier);
    }
    
    /**
     * Remove a handler by its class name.
     * 
     * @param string $className The full class name of the handler
     * 
     * @return bool True if a handler was removed, false otherwise
     */
    private static function unregisterHandlerByClassName(string $className): bool {
        if (!class_exists($className)) {
            return false;
        }
        
        foreach (self::get()->handlersPool as $existingHandler) {
            if (get_class($existingHandler) === $className) {
                return self::unregisterHandler($existingHandler);
            }
        }
        
        return false;
    }
    
    /**
     * Remove a registered errors handler.
     * 
     * @param AbstractHandler $h A class that implements a custom handler.
     */
    public static function unregisterHandler(AbstractHandler $h): bool {
        $tempPool = [];
        $removed = false;

        foreach (self::get()->handlersPool as $handler) {
            if ($handler->getName() !== $h->getName()) {
                $tempPool[] = $handler;
            } else {
                // Clean up the handler before removing it (but avoid null assignments)
                $handler->cleanup();
                $removed = true;
            }
        }
        self::get()->handlersPool = $tempPool;

        // Clean up execution count for removed handler
        unset(self::$handlerExecutionCount[$h->getName()]);
        
        // Trigger memory cleanup if needed
        if (memory_get_usage() > self::$memoryThreshold * 0.8) {
            self::cleanupMemory();
        }

        return $removed;
    }
    
    /**
     * Log handler failure safely without triggering another handler.
     * 
     * @param AbstractHandler $handler The handler that failed
     * @param Throwable $exception The exception that caused the failure
     */
    private function logHandlerFailure(AbstractHandler $handler, Throwable $exception): void {
        // Use basic error_log to avoid triggering another handler
        $logMessage = sprintf(
            'Handler "%s" failed: %s in %s:%d',
            $handler->getName(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        error_log($logMessage);
    }
    
    /**
     * Handle failure fallback without triggering handlers.
     * 
     * @param Throwable $exception The exception that caused the failure
     */
    private function handleFailureFallback(Throwable $exception): void {
        // Simple, safe error output that won't trigger handlers
        if (php_sapi_name() === 'cli') {
            fprintf(STDERR, "Error Handler Failed: %s\n", $exception->getMessage());
        } else {
            // For web requests, output minimal safe HTML
            echo 'An error occurred in the error handler. Please check the error logs.';
        }
    }
    
    /**
     * Reset handler execution counts (useful for long-running processes).
     */
    public static function resetExecutionCounts(): void {
        self::$handlerExecutionCount = [];
    }
    
    /**
     * Set the maximum number of executions allowed per handler.
     * 
     * @param int $max Maximum executions (must be > 0)
     */
    public static function setMaxHandlerExecutions(int $max): void {
        if ($max > 0) {
            self::$maxHandlerExecutions = $max;
        }
    }
    
    /**
     * Get current execution count for a handler.
     * 
     * @param string $handlerName Name of the handler
     * @return int Current execution count
     */
    public static function getHandlerExecutionCount(string $handlerName): int {
        return self::$handlerExecutionCount[$handlerName] ?? 0;
    }
    
    /**
     * Set configuration for the error handler.
     * 
     * @param HandlerConfig $config Configuration instance
     */
    public static function setConfig(HandlerConfig $config): void {
        // Restore previous config if it exists
        if (self::$config !== null) {
            self::$config->restore();
        }
        
        self::$config = $config;
        
        // Apply new configuration if handler is already initialized
        if (self::$inst !== null) {
            self::$config->apply();
        }
    }
    
    /**
     * Get current configuration.
     * 
     * @return HandlerConfig
     */
    public static function getConfig(): HandlerConfig {
        if (self::$config === null) {
            self::$config = new HandlerConfig();
        }
        
        return self::$config;
    }
    
    /**
     * Reset configuration to defaults.
     */
    public static function resetConfig(): void {
        if (self::$config !== null) {
            self::$config->restore();
        }
        
        self::$config = new HandlerConfig();
        
        if (self::$inst !== null) {
            self::$config->apply();
        }
    }
    
    /**
     * Clean up memory by removing unused handler references and resetting counters.
     * Should be called periodically in long-running processes.
     */
    public static function cleanupMemory(): void {
        // Clean up execution counters for handlers that no longer exist
        $activeHandlerNames = [];
        foreach (self::get()->handlersPool as $handler) {
            $activeHandlerNames[] = $handler->getName();
        }
        
        // Remove execution counts for non-existent handlers
        self::$handlerExecutionCount = array_intersect_key(
            self::$handlerExecutionCount,
            array_flip($activeHandlerNames)
        );
        
        // Clean up weak references
        self::$handlerWeakRefs = array_filter(self::$handlerWeakRefs, function($weakRef) {
            return $weakRef->get() !== null;
        });
        
        // Force garbage collection if memory usage is high
        if (memory_get_usage() > self::$memoryThreshold) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Get memory usage statistics.
     * 
     * @return array<string, mixed> Memory usage information
     */
    public static function getMemoryStats(): array {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'handler_count' => count(self::get()->handlersPool),
            'execution_counters' => count(self::$handlerExecutionCount),
            'weak_references' => count(self::$handlerWeakRefs),
            'threshold' => self::$memoryThreshold
        ];
    }
    
    /**
     * Set memory threshold for automatic cleanup.
     * 
     * @param int $bytes Memory threshold in bytes
     */
    public static function setMemoryThreshold(int $bytes): void {
        if ($bytes > 0) {
            self::$memoryThreshold = $bytes;
        }
    }
    
    /**
     * Remove all handlers and clean up memory completely.
     * Use with caution - this will remove all error handling.
     */
    public static function shutdown(): void {
        $instance = self::$inst;
        if ($instance !== null) {
            // Clean up all handlers (but don't call cleanup() to avoid null assignment issues)
            $instance->handlersPool = [];
            $instance->lastException = null;
            
            // Clean up static data
            self::$handlerExecutionCount = [];
            self::$handlerWeakRefs = [];
            self::$isHandlingException = false;
            
            // Restore original PHP configuration
            if (self::$config !== null) {
                self::$config->restore();
                self::$config = null;
            }
            
            // Restore original error handler
            restore_error_handler();
            
            // Force garbage collection
            gc_collect_cycles();
        }
    }
}
