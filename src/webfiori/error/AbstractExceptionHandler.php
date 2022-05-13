<?php
namespace webfiori\error;

use Throwable;
/**
 * This class is used to implement custom exceptions handler.
 *
 * @author Ibrahim
 */
abstract class AbstractExceptionHandler {
    private $exception;
    private $traceArr;
    /**
     * Creates new instance of the class.
     */
    public function __construct() {
        $this->traceArr = [];
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

            foreach ($trace as $traceEntry) {
                $this->traceArr[] = new TraceEntry($traceEntry);
            }
        }
    }
}
