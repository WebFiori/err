<?php
namespace webfiori\error;

use Throwable;
/**
 * The core class which is used to define errors and exceptions handling.
 *
 * @author Ibrahim
 */
class Handler {
    /**
     * An array which holds one constant that is used to hold the meanings of different
     * PHP errors.
     * 
     * This is used in converting errors to exceptions.
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
        E_STRICT => [
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
     * 
     * @var AbstractExceptionHandler
     */
    private $handler;
    /**
     * 
     * @var Handler
     */
    private static $inst;
    private function __construct() {
        ini_set('display_startup_errors', 1);
        ini_set('display_errors', 1);
        error_reporting(-1);
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline)
        {
            $errClass = TraceEntry::extractClassName($errfile);
            $errType = Handler::ERR_TYPES[$errno];
            $message = $errType['description'].': '.$errstr.' at '.$errClass.' Line '.$errline;
            throw new ErrorHandlerException($message, $errno, $errfile);
        });
        set_exception_handler(function (Throwable $ex)
        {
            $class = Handler::get()->handler;
            $class->setException($ex);
            $class->handle();
        });
        $this->handler = new DefaultExceptionsHandler();
    }
    /**
     * Returns the instance which is used to handle exceptions and errors.
     * 
     * @return Handler An instance of the class.
     */
    public static function get() {
        if (self::$inst === null) {
            self::$inst = new Handler();
        }

        return self::$inst;
    }
    /**
     * Sets a custom handler to handle exceptions.
     * 
     * @param AbstractExceptionHandler $h A class that implements a custom
     * handler.
     */
    public static function setHandler(AbstractExceptionHandler $h) {
        self::get()->handler = $h;
    }
    /**
     * Returns the handler instance which is used to handle exceptions.
     * 
     * @return AbstractExceptionHandler The handler instance which is used to
     * handle exceptions.
     */
    public function getHandler() : AbstractExceptionHandler {
        return self::get()->handler;
    }
}
