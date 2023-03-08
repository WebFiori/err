<?php
namespace webfiori\error;

/**
 * The default exceptions handler.
 * 
 * This simple handler will show the exception alongside the message ant trace
 * using 'echo'.
 *
 * @author Ibrahim
 */
class DefaultHandler extends AbstractHandler {
    /**
     * Creates new instance of the class.
     */
    public function __construct() {
        parent::__construct();
        $this->setName('Default');
    }
    /**
     * Handles the exception.
     */
    public function handle() {
        echo '<pre>';
        echo 'An exception was thrown at '.$this->getClass().' line '.$this->getLine().'.<br>';
        echo 'Exception message: '.$this->getMessage().'.<br>';
        echo 'Stack trace:<br>';
        $trace = $this->getTrace();
        
        if (count($trace) == 0) {
            echo '&lt;Empty&gt;';
        } else {
            $index = '0';

            foreach ($trace as $entry) {
                echo '#'.$index.' '.$entry.'<br>';
                $index++;
            }
        }
        echo '</pre>';
    }

    /**
     * Checks if the handler is active or not.
     *
     * @return bool The method will always return true.
     */
    public function isActive(): bool {
        return true;
    }

    /**
     * Checks if the handler will be executed as a shutdown handler.
     *
     * @return bool The method will always return true.
     */
    public function isShutdownHandler(): bool {
        return true;
    }

}
