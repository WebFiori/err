<?php
namespace webfiori\error;

use Throwable;
/**
 * The core class which is used to define errors and exceptions handling.
 *
 * @author Ibrahim
 */
class Handler {
    private $handlersPool;
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
     * @var AbstractHandler
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
            $message = 'An exception caused by an error. '.$errType['description'].': '.$errstr.' at '.$errClass.' Line '.$errline;
            throw new ErrorHandlerException($message, $errno, $errfile);
        });
        set_exception_handler(function (Throwable $ex)
        {
            foreach (Handler::get()->handlersPool as $h) {
                
                if ($h->isActive()) {
                    $h->setException($ex);
                    $h->handle();
                    $h->setIsExecuted(true);
                }
            }
        });
        register_shutdown_function(function () {
            $lastErr = error_get_last();
            
            if ($lastErr !== null) {
                if (ob_get_length()) {
                    ob_clean();
                }
                $errClass = TraceEntry::extractClassName($lastErr['file']);
                $errType = Handler::ERR_TYPES[$lastErr['type']];
                $message = $errType['description'].': '.$lastErr['message'].' At '.$errClass.' Line '.$lastErr['line'];
                $ex = new ErrorHandlerException($message, $lastErr['type'], $lastErr['file']);
                foreach (Handler::get()->handlersPool as $h) {

                    if ($h->isActive() && $h->isShutdownHandler() && !$h->isExecuted()) {
                        $h->setException($ex);
                        $h->handle();
                        $h->setIsExecuted(true);
                    }
                }
            }
        });
        $this->handlersPool = [];
        $this->handlersPool[] = new DefaultHandler();
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
     * @param AbstractHandler $h A class that implements a custom
     * handler.
     */
    public static function registerHandler(AbstractHandler $h) {
        if (!self::hasHandler($h->getName())) {
            self::get()->handlersPool[] = $h;
        }
    }
    /**
     * Remove a registered errors handler.
     * 
     * @param AbstractHandler $h A class that implements a custom
     * handler.
     */
    public static function unregisterHandler(AbstractHandler $h) : bool {
        $tempPool = [];
        $removed = false;
        foreach (self::get()->handlersPool as $handler) {
            if ($handler->getName() != $h->getName()) {
                $tempPool[] = $handler;
                continue;
            }
            $removed = true;
        }
        self::get()->handlersPool = $tempPool;
        return $removed;
    }
    /**
     * Returns a handler given its name.
     * 
     * @param string $name The name of the handler.
     * 
     * @return AbstractHandler|null If a handler which has the given name is found,
     * it will be returned as an object. Other than that, null is returned.
     */
    public static function &getHandler(string $name) {
        $h = null;
        $trimmed = trim($name);
        
        foreach (self::get()->handlersPool as $handler) {
            if ($handler->getName() == $trimmed) {
                $h = $handler;
                break;
            }
        }
        return $h;
    }
    /**
     * Checks if a handler is registered or not given its name.
     * 
     * @param string $name The name of the handler.
     * 
     * @return bool If such handler is registered, the method will return true.
     * Other than that, the method will return false.
     */
    public static function hasHandler(string $name) : bool {
        $trimmed = trim($name);
        foreach (self::get()->handlersPool as $handler) {
            if ($handler->getName() == $trimmed) {
                return true;
            }
        }
        return false;
    }
}
