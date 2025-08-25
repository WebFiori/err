<?php
namespace WebFiori\Error;

use Throwable;
use WebFiori\Error\Security\SecurityConfig;
use WebFiori\Error\Security\PathSanitizer;
use WebFiori\Error\Security\StackTraceFilter;
use WebFiori\Error\Security\OutputSanitizer;
use WebFiori\Error\Security\SecurityMonitor;

/**
 * Abstract base class for implementing custom exception handlers with built-in security.
 * 
 * This class provides the foundation for creating custom exception handlers
 * that can be registered with the Handler class. Each handler can:
 * - Define its own priority for execution order
 * - Choose whether to run during normal execution or shutdown
 * - Access detailed exception information and stack traces (filtered based on environment)
 * - Implement custom handling logic with automatic processing
 * 
 * All data access methods automatically apply filtering based on the
 * current environment (development, staging, production).
 * 
 * Usage Example:
 * ```php
 * class MyHandler extends AbstractHandler {
 *     public function __construct() {
 *         parent::__construct();
 *         $this->setName('MyHandler');
 *         $this->setPriority(10);
 *     }
 * 
 *     public function handle(): void {
 *         // All output is automatically processed
 *         $this->secureOutput('<div class="error">');
 *         $this->secureOutput('Error in: ' . $this->getClass());
 *         $this->secureOutput('Message: ' . $this->getMessage());
 *         $this->secureOutput('</div>');
 *     }
 * 
 *     public function isActive(): bool {
 *         return true;
 *     }
 * 
 *     public function isShutdownHandler(): bool {
 *         return false;
 *     }
 * }
 * ```
 *
 * @author Ibrahim
 */
abstract class AbstractHandler {
    /**
     * @var Throwable|null
     */
    private ?Throwable $exception = null;
    
    /**
     * @var bool
     */
    private bool $isCalled;
    
    /**
     * @var bool
     */
    private bool $isExecuting;
    
    /**
     * @var string
     */
    private string $name;
    
    /**
     * @var array<TraceEntry>
     */
    private array $traceArr;
    
    /**
     * @var int
     */
    private int $priority;
    
    /**
     * Security components
     */
    private SecurityConfig $security;
    private PathSanitizer $pathSanitizer;
    private StackTraceFilter $traceFilter;
    private OutputSanitizer $outputSanitizer;
    private SecurityMonitor $monitor;
    
    /**
     * Creates new instance of the class.
     */
    public function __construct() {
        $this->traceArr = [];
        $this->name = 'New Handler';
        $this->isCalled = false;
        $this->isExecuting = false;
        $this->priority = 0;
        
        $this->initializeSecurity();
    }
    
    /**
     * Initialize security components.
     */
     private function initializeSecurity(): void {
        $this->security = $this->createSecurityConfig();
        $this->pathSanitizer = new PathSanitizer($this->security);
        $this->traceFilter = new StackTraceFilter($this->security, $this->pathSanitizer);
        $this->outputSanitizer = new OutputSanitizer($this->security);
        $this->monitor = new SecurityMonitor($this->security);
    }
    
    /**
     * Create security configuration. Can be overridden by subclasses.
     */
    protected function createSecurityConfig(): SecurityConfig {
        return new SecurityConfig();
    }
    
    /**
     * Returns the priority of the handler.
     * 
     * The priority is a number which is used to set execution order of
     * handlers. A positive number indicates that the handler has higher priority
     * and will get executed first.
     * 
     * @return int A number that represents the priority. Default is 0.
     */
    public function getPriority(): int {
        return $this->priority;
    }
    
    /**
     * Sets the priority of the handler.
     * 
     * The priority is a number which is used to set execution order of
     * handlers. A positive number indicates that the handler has higher priority
     * and will get executed first.
     * 
     * @param int $priority A number that represents the priority. It must be
     * a non-negative value.
     */
    public function setPriority(int $priority): void {
        if ($priority >= 0) {
            $this->priority = $priority;
        }
    }
    
    /**
     * Returns a sanitized class name.
     * Automatically filters sensitive path information based on environment.
     * 
     * @return string A string that represents the name of the class that an exception
     * was thrown at.
     */
    public function getClass(): string {
        $rawClass = $this->getRawClass();
        return $this->pathSanitizer->sanitizeClassName($rawClass);
    }
    
    /**
     * Returns exception error code.
     * 
     * @return string Error code of the exception.
     */
    public function getCode(): string {
        return $this->exception !== null ? (string)$this->exception->getCode() : '0';
    }
    
    /**
     * Returns the exception object only if configuration allows it.
     * In production, this returns null to prevent information disclosure.
     * 
     * @return Throwable|null An object that represents the exception which was thrown.
     */
    public function getException(): ?Throwable {
        if (!$this->security->allowRawExceptionAccess()) {
            $this->monitor->recordSecurityViolation('getException', $this);
            return null;
        }
        return $this->exception;
    }
    
    /**
     * Returns a line number.
     * May hide line numbers in production environments.
     * 
     * @return string The number of line at which the exception was thrown at.
     */
    public function getLine(): string {
        if (!$this->security->shouldShowLineNumbers()) {
            return '(Hidden for security)';
        }
        return $this->getRawLine();
    }
    
    /**
     * Returns a sanitized exception message.
     * Removes sensitive information like passwords, tokens, etc.
     * 
     * @return string A string that represents exception message.
     */
    public function getMessage(): string {
        $rawMessage = $this->getRawMessage();
        return $this->outputSanitizer->sanitizeMessage($rawMessage);
    }
    
    /**
     * Returns the name of the handler.
     * 
     * @return string The name of the handler.
     */
    public function getName(): string {
        return $this->name;
    }
    
    /**
     * Returns a filtered stack trace.
     * Automatically removes sensitive paths and limits depth.
     * 
     * @return array<TraceEntry> An array that holds objects of type 'TraceEntry'
     */
    public function getTrace(): array {
        $rawTrace = $this->getRawTrace();
        return $this->traceFilter->filterTrace($rawTrace);
    }
    
    /**
     * Output method that all handlers should use.
     * Automatically sanitizes content based on configuration.
     * 
     * @param string $content The content to output
     */
    protected function secureOutput(string $content): void {
        $sanitized = $this->outputSanitizer->sanitize($content);
        echo $sanitized;
    }
    
    /**
     * Logging method with automatic context sanitization.
     * 
     * @param string $message The log message
     * @param array $context Additional context data
     */
    public function secureLog(string $message, array $context = []): void {
        $sanitizedMessage = $this->outputSanitizer->sanitizeMessage($message);
        $sanitizedContext = $this->outputSanitizer->sanitizeContext($context);
        
        error_log(json_encode([
            'message' => $sanitizedMessage,
            'context' => $sanitizedContext,
            'timestamp' => time(),
            'handler' => $this->getName()
        ]));
    }
    
    /**
     * Check if we're in a production environment.
     * 
     * @return bool True if in production environment
     */
    protected function isSecureEnvironment(): bool {
        return $this->security->isProduction();
    }
    
    /**
     * Get configuration for custom logic.
     * 
     * @return SecurityConfig The configuration
     */
    protected function getSecurityConfig(): SecurityConfig {
        return $this->security;
    }
    
    /**
     * Handles the exception.
     * 
     * The developer can implement this method to handle all thrown exceptions.
     * All data access methods automatically apply filtering, and all
     * output should use secureOutput() method for automatic sanitization.
     */
    public abstract function handle(): void;
    
    /**
     * Checks if the handler will be used to handle errors or not.
     * 
     * The developer must implement this method in a way it returns true if the
     * handler will get executed. False otherwise.
     */
    public abstract function isActive(): bool;
    
    /**
     * Checks if the handler was executed once or not.
     * 
     * @return bool If the method returned true, then this means the handler
     * was executed.
     */
    public function isExecuted(): bool {
        return $this->isCalled;
    }
    
    /**
     * Check if the handler is in execution stage or not.
     * 
     * This method is used to indicate if execution
     * scope is inside the method AbstractHandler::handle() or not.
     * 
     * @return bool True if the handler is executing. False if not.
     */
    public function isExecuting(): bool {
        return $this->isExecuting;
    }
    
    /**
     * Checks if the handler will be called in case of error after shutdown.
     * 
     * Note that if the handler is set as shutdown handler, it will not
     * get executed during normal events.
     */
    public abstract function isShutdownHandler(): bool;
    
    /**
     * Sets the exception which was thrown by an error on the code.
     * 
     * This method is called internally by the exception handling method.
     * 
     * @param Throwable $ex The exception which was thrown by the code.
     */
    public function setException(Throwable $ex): void {
        $this->exception = $ex;
        $this->setTrace();
    }
    
    /**
     * Sets the handler as executed.
     * 
     * This method is used to make sure that same handler won't get executed twice.
     * 
     * @param bool $bool True to set it as executed, false to not.
     */
    public function setIsExecuted(bool $bool): void {
        $this->isCalled = $bool;
    }
    
    /**
     * Sets the value that tells if the handler is being executed or not.
     * 
     * This method is used internally by the library to set status of the
     * handler.
     * 
     * @param bool $isExec True to set the handler as being executed. False
     * to not.
     */
    public function setIsExecuting(bool $isExec): void {
        $this->isExecuting = $isExec;
    }
    
    /**
     * Gives the handler a specific name.
     * 
     * @param string $name The custom name of the handler.
     */
    public function setName(string $name): void {
        $this->name = trim($name);
    }
    
    /**
     * Internal method to get raw class name.
     * Protected so only the framework can access it.
     */
    protected function getRawClass(): string {
        return TraceEntry::extractClassName(
            $this->exception?->getFile() ?? 'Unknown'
        );
    }
    
    /**
     * Internal method to get raw line number.
     */
    protected function getRawLine(): string {
        return $this->exception !== null ? (string)$this->exception->getLine() : '(Unknown Line)';
    }
    
    /**
     * Internal method to get raw message.
     */
    protected function getRawMessage(): string {
        return $this->exception !== null ? $this->exception->getMessage() : 'No Message';
    }
    
    /**
     * Internal method to get raw trace.
     */
    protected function getRawTrace(): array {
        return $this->traceArr;
    }
    
    /**
     * Fallback when a handler fails.
     */
    public function handleSecurityFallback(Throwable $handlerException): void {
        if ($this->security->isProduction()) {
            $this->secureOutput('<p>An error occurred. Please contact support.</p>');
        } else {
            $this->secureOutput('<p>Handler failed: ' . htmlspecialchars($handlerException->getMessage()) . '</p>');
        }
    }
    
    /**
     * Clean up handler resources to prevent memory leaks.
     * Called when handler is removed or system is shutting down.
     */
    public function cleanup(): void {
        // Clear exception reference
        $this->exception = null;
        
        // Reset execution state
        $this->isCalled = false;
        $this->isExecuting = false;
        
        // Note: We cannot set typed properties to null in PHP 7.4+
        // Instead, we'll let the garbage collector handle cleanup
        // when the handler object is destroyed
    }
    
    /**
     * Get memory usage for this handler.
     * 
     * @return array<string, mixed> Memory usage information
     */
    public function getMemoryUsage(): array {
        $usage = [
            'handler_name' => $this->getName(),
            'has_exception' => $this->exception !== null,
            'is_executed' => $this->isCalled,
            'is_executing' => $this->isExecuting,
            'security_components' => [
                'security_config' => isset($this->security),
                'path_sanitizer' => isset($this->pathSanitizer),
                'trace_filter' => isset($this->traceFilter),
                'output_sanitizer' => isset($this->outputSanitizer),
                'monitor' => isset($this->monitor)
            ]
        ];
        
        if ($this->exception !== null) {
            $usage['exception_size'] = strlen(serialize($this->exception));
        }
        
        return $usage;
    }
    
    /**
     * Sets the trace array based on the current exception.
     */
    private function setTrace(): void {
        $ex = $this->getException();
        
        if ($ex === null) {
            $this->traceArr = [];
            return;
        }

        if ($ex instanceof ErrorHandlerException) {
            $this->traceArr = $ex->getDebugTrace();
        } else {
            $trace = $ex->getTrace();
            $currentLine = $trace[0]['line'] ?? '(Unknown Line)';
            $currentFile = $trace[0]['file'] ?? '(Unknown File)';
            $idx = 0;

            foreach ($trace as $traceEntry) {
                if ($idx !== 0) {
                    $nextFile = $traceEntry['file'] ?? '(Unknown File)';
                    $nextLine = $traceEntry['line'] ?? '(Unknown Line)';
                    $traceEntry['file'] = $currentFile;
                    $traceEntry['line'] = $currentLine;
                    $this->traceArr[] = new TraceEntry($traceEntry);
                    $currentFile = $nextFile;
                    $currentLine = $nextLine;
                }
                $idx++;
            }
        }
    }
}
