<?php
namespace WebFiori\Error;

use Throwable;
/**
 * This class is used to implement custom exception handler.
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
     * Creates new instance of the class.
     */
    public function __construct() {
        $this->traceArr = [];
        $this->name = 'New Handler';
        $this->isCalled = false;
        $this->isExecuting = false;
        $this->priority = 0;
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
     * Returns a string that represents the name of the class that an exception
     * was thrown at.
     * 
     * @return string A string that represents the name of the class that an exception
     * was thrown at.
     */
    public function getClass(): string {
        return TraceEntry::extractClassName($this->getException() !== null ? $this->getException()->getFile() : 'X');
    }
    
    /**
     * Returns exception error code.
     * 
     * @return string Error code of the exception.
     */
    public function getCode(): string {
        return $this->getException() !== null ? (string)$this->getException()->getCode() : '0';
    }
    
    /**
     * Returns an object that represents the exception which was thrown.
     * 
     * @return Throwable|null An object that represents the exception which was thrown.
     */
    public function getException(): ?Throwable {
        return $this->exception;
    }
    
    /**
     * Returns the number of line at which the exception was thrown at.
     * 
     * @return string The number of line at which the exception was thrown at.
     */
    public function getLine(): string {
        return $this->getException() !== null ? (string)$this->getException()->getLine() : '(Unknown Line)';
    }
    
    /**
     * Returns a string that represents exception message.
     * 
     * @return string A string that represents exception message.
     */
    public function getMessage(): string {
        return $this->getException() !== null ? $this->getException()->getMessage() : 'No Message';
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
     * Returns an array that contains objects that represents stack trace of
     * the call.
     * 
     * @return array<TraceEntry> An array that holds objects of type 'TraceEntry'
     */
    public function getTrace(): array {
        return $this->traceArr;
    }
    
    /**
     * Handles the exception.
     * 
     * The developer can implement this method to handle all thrown exceptions.
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
