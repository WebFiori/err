<?php
namespace webfiori\error;

use Throwable;
/**
 * This class is used to implement custom exception handler.
 *
 * @author Ibrahim
 */
abstract class AbstractHandler {
    private $exception;
    private $isCalled;
    private $isExecuting;
    private $name;
    private $traceArr;
    /**
     * Creates new instance of the class.
     */
    public function __construct() {
        $this->traceArr = [];
        $this->name = 'New Handler';
        $this->isCalled = false;
        $this->isExecuting = false;
    }
    /**
     * Returns a string that represents the name of the class that an exception
     * was thrown at.
     * 
     * @return string A string that represents the name of the class that an exception
     * was thrown at.
     */
    public function getClass() : string {
        return TraceEntry::extractClassName($this->getException()->getFile());
    }
    /**
     * Returns exception error code.
     * 
     * @return string Error code of the exception.
     */
    public function getCode() : string {
        return $this->getException()->getCode().'';
    }
    /**
     * Returns an object that represents the exception which was thrown.
     * 
     * @return Throwable An object that represents the exception which was thrown.
     */
    public function getException() : Throwable {
        return $this->exception;
    }
    /**
     * Returns the number of line at which the exception was thrown at.
     * 
     * @return string The number of line at which the exception was thrown at.
     */
    public function getLine() : string {
        return $this->getException()->getLine().'';
    }
    /**
     * Returns a string that represents exception message.
     * 
     * @return string A string that represents exception message.
     */
    public function getMessage() : string {
        return $this->getException()->getMessage();
    }
    /**
     * Returns the name of the handler.
     * 
     * @return string The name of the handler.
     */
    public function getName() : string {
        return $this->name;
    }
    /**
     * Returns an array that contains objects that represents stack trace of
     * the call.
     * 
     * @return array An array that holds objects of type 'StackEntry'
     */
    public function getTrace() : array {
        return $this->traceArr;
    }
    /**
     * Handles the exception.
     * 
     * The developer can implement this method to handle all thrown exceptions.
     */
    public abstract function handle();
    /**
     * Checks if the handler will be used to handle errors or not.
     * 
     * The developer must implement this method in a way it returns true if the
     * handler will get executed. False otherwise.
     */
    public abstract function isActive() : bool;
    /**
     * Checks if the handler was executed once or not.
     * 
     * @return bool If the method returned true, then this means the handler
     * was executed.
     */
    public function isExecuted() : bool {
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
    public function isExecuting() : bool {
        return $this->isExecuting;
    }
    /**
     * Checks if the handler will be called in case of error after shutdown.
     */
    public abstract function isShutdownHandler() : bool;
    /**
     * Sets the exception which was thrown by an error on the code.
     * 
     * This method is called internally by the exception handling method.
     * 
     * @param Throwable $ex The exception which was thrown by the code.
     */
    public function setException(Throwable $ex) {
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
    public function setIsExecuted(bool $bool) {
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
    public function setIsExecuting(bool $isExec) {
        $this->isExecuting = $isExec;
    }
    /**
     * Gives the handler a specific name.
     * 
     * @param string $name The custom name of the handler.
     */
    public function setName(string $name) {
        $this->name = trim($name);
    }
    private function setTrace() {
        $ex = $this->getException();

        if ($ex instanceof ErrorHandlerException) {
            $this->traceArr = $ex->getDebugTrace();
        } else {
            $trace = $ex->getTrace();
            $currentLine = $trace[0]['line'] ?? 'X';
            $currentFile = $trace[0]['file'] ?? 'X';
            $idx = 0;

            foreach ($trace as $traceEntry) {
                if ($idx != 0) {
                    $nextFile = $traceEntry['file'] ?? 'X';
                    $nextLine = $traceEntry['line'] ?? 'X';
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
