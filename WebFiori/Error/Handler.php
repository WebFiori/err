<?php
namespace WebFiori\Error;

use Exception;
use Throwable;
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
        $this->initializeErrorReporting();
        $this->createHandlers();
        $this->registerPhpHandlers();
        $this->initializeHandlerPool();
    }
    
    /**
     * Initialize PHP error reporting settings.
     */
    private function initializeErrorReporting(): void {
        ini_set('display_startup_errors', '1');
        ini_set('display_errors', '1');
        error_reporting(-1);
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
     * Execute a single handler with proper state management.
     * 
     * @param AbstractHandler $handler The handler to execute
     * @param Throwable|null $exception The exception to handle
     */
    private function executeHandler(AbstractHandler $handler, ?Throwable $exception): void {
        if ($exception instanceof Throwable) {
            $handler->setException($exception);
        }
        
        $handler->setIsExecuting(true);
        
        try {
            $handler->handle();
        } catch (Throwable $handlerException) {
            // Prevent infinite loops - log handler failures
            error_log(sprintf(
                'Handler "%s" failed: %s',
                $handler->getName(),
                $handlerException->getMessage()
            ));
        } finally {
            $handler->setIsExecuting(false);
            $handler->setIsExecuted(true);
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
                $removed = true;
            }
        }
        self::get()->handlersPool = $tempPool;

        return $removed;
    }
}
