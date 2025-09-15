<?php
namespace WebFiori\Error;

use ErrorException;
/**
 * This class is used to represent PHP errors which was converted to exceptions.
 *
 * @author Ibrahim
 */
class ErrorHandlerException extends ErrorException {
    private $debugTrace;
    /**
     * Creates new instance of the class.
     * 
     * @param string $message The message of the exception.
     * 
     * @param int $code The error code of the PHP error.
     * 
     * @param string $file The path to the file at which the error happened.
     */
    public function __construct(string $message = "", int $code = 0, string $file = '', int $line = 0) {
        parent::__construct($message, $code, $code, $file, $line);
        $this->debugTrace = [];
        $trace = debug_backtrace();
        $line = null;

        for ($x = 0 ; $x < count($trace) ; $x++) {
            if ($x == 1) {
                $line = $trace[$x]['line'] ?? 'X';
                continue;
            }

            if ($x > 1) {
                $temp = $trace[$x];
                $temp['line'] = $line;
                $this->debugTrace[] = new TraceEntry($temp);
                $line = $trace[$x]['line'] ?? 'X';
            }
        }
        $this->debugTrace[] = new TraceEntry([
            'file' => $file,
            'line' => $line
        ]);
    }
    /**
     * Returns an array that contains stack trace of the error.
     * 
     * @return array An array that holds objects of type 'TraceEntry'.
     */
    public function getDebugTrace() : array {
        return $this->debugTrace;
    }
}
