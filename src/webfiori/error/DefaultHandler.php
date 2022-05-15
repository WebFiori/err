<?php
namespace webfiori\error;

/**
 * The default exceptions handler.
 * 
 * This simple handler will show the exception alongside the message ant trace
 * using the 'echo' command.
 *
 * @author Ibrahim
 */
class DefaultHandler extends AbstractHandler {
    /**
     * Creates new instance of the class.
     */
    public function __construct() {
        parent::__construct();
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

    public function isActive(): bool {
        return true;
    }

    public function isShutdownHandler(): bool {
        return true;
    }

}
