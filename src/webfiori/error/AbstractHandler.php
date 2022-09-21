<?php
namespace webfiori\error;

use Throwable;
/**
 * This class is used to implement custom exceptions handler.
 *
 * @author Ibrahim
 */
abstract class AbstractHandler {
    private $exception;
    private $traceArr;
    private $name;
    private $isCalled;
    /**
     * Creates new instance of the class.
     */
    public function __construct() {
        $this->traceArr = [];
        $this->name = 'New Handler';
        $this->isCalled = false;
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
     * Checks if the handler was executed once or not.
     * 
     * @return bool If the method returned true, then this means the handler
     * was executed.
     */
    public function isExecuted() : bool {
        return $this->isCalled;
    }
    /**
     * Gives the handler a specific name.
     * 
     * @param string $name The custom name of the handler.
     */
    public function setName(string $name) {
        $this->name = trim($name);
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
     * Returns a string that represents the name of the class that an exception
     * was thrown at.
     * 
     * @return string A string that represents the name of the class that an exception
     * was thrown at.
     */
    public function getClass() {
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
    private function setTrace() {
        $ex = $this->getException();
        
        if ($ex instanceof ErrorHandlerException) {
            $this->traceArr = $ex->getDebugTrace();
        } else {
            $trace = $ex->getTrace();
            $currentLine = isset($trace[0]['line']) ? $trace[0]['line'] : 'X';
            $currentFile = isset($trace[0]['file']) ? $trace[0]['file'] : 'X';
            $nextLine = '';
            $nextFile = '';
            $idx = 0;
            foreach ($trace as $traceEntry) {
                if ($idx != 0) {
                    $nextFile = isset($traceEntry['file']) ? $traceEntry['file'] : 'X';
                    $nextLine = isset($traceEntry['line']) ? $traceEntry['line'] : 'X';
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
