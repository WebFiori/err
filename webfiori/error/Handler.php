<?php
namespace webfiori\error;

use Exception;
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
    private $handlersPool;
    /**
     * 
     * @var Handler
     */
    private static $inst;
    /**
     * 
     * @var AbstractHandler
     */
    private $isErrOccured;
    /**
     * 
     * @var Throwable|null
     */
    private $lastException;
    private $errToExceptionHandler;
    private $exceptionsHandler;
    private $shutdownFunction;
    private function __construct() {
        ini_set('display_startup_errors', 1);
        ini_set('display_errors', 1);
        error_reporting(-1);
        $this->errToExceptionHandler = function (int $errno, string $errString, string $errFile, int $errLine) {
            //Convert errors to exceptions
            $errClass = TraceEntry::extractClassName($errFile);
            $errType = Handler::ERR_TYPES[$errno];
            $message = 'An exception caused by an error. '.$errType['description'].': '.$errString.' at '.$errClass.' Line '.$errLine;
            throw new ErrorHandlerException($message, $errno, $errFile, $errLine);
        };
        $this->exceptionsHandler = function (Throwable $ex = null) {
            Handler::get()->lastException = $ex;
            Handler::get()->sortHandlers();
            foreach (Handler::get()->handlersPool as $h) {
                if ($h->isActive() && !$h->isShutdownHandler()) {
                    if ($ex instanceof Throwable) {
                        $h->setException($ex);
                    }
                    $h->setIsExecuting(true);
                    $h->handle();
                    $h->setIsExecuting(false);
                    $h->setIsExecuted(true);
                }
            }
        };
        $this->shutdownFunction = function () {
            $lastException = Handler::get()->lastException;
            if ($lastException !== null) {
                if (ob_get_length()) {
                    ob_clean();
                }

                foreach (Handler::get()->handlersPool as $h) {
                    if ($h->isActive() && $h->isShutdownHandler() && !$h->isExecuted() && !$h->isExecuting()) {
                        if ($lastException instanceof Throwable) {
                            $h->setException($lastException);
                        }
                        $h->handle();
                        $h->setIsExecuted(true);
                    }
                }
            }
        };
        $this->isErrOccured = false;
        set_error_handler($this->errToExceptionHandler);
        set_exception_handler($this->exceptionsHandler);
        register_shutdown_function($this->shutdownFunction);
        $this->handlersPool = [];
        $this->handlersPool[] = new DefaultHandler();
    }
    public function invokShutdownHandler() {
        self::get()->lastException = 'TEST';
        call_user_func(self::get()->shutdownFunction);
    }
    /**
     * Sort all registered handlers based on their priority.
     * 
     * The ones with higher priority will come first.
     */
    public function sortHandlers() {
        $customSortFunc = function (AbstractHandler $first, AbstractHandler $second) {
            return $second->getPriority() - $first->getPriority();
        };
        usort($this->handlersPool, $customSortFunc);
    }
    public function invokExceptionHandler() {
        call_user_func(self::get()->exceptionsHandler);
    }
    public static function reset() {
        $h = self::get();
        $h->handlersPool = [];
        $h->handlersPool[] = new DefaultHandler();
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
     * Returns an array that contains all registered handlers as objects.
     * 
     * @return array
     */
    public static function getHandlers() : array {
        return self::get()->handlersPool;
    }
    /**
     * Returns the instance which is used to handle exceptions and errors.
     * 
     * @return Handler An instance of the class.
     */
    public static function get() : Handler {
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
    public static function hasHandler(string $name) : bool {
        $trimmed = trim($name);

        foreach (self::get()->handlersPool as $handler) {
            if ($handler->getName() == $trimmed) {
                return true;
            }
        }

        return false;
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
}
