<?php
namespace WebFiori\Error;

/**
 * The default exceptions handler.
 * 
 * This simple handler will show the exception alongside the message and trace
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
        echo '<pre>'."\n";
        echo 'An exception was thrown at '.$this->getClass().' line '.$this->getLine().".\n";
        echo 'Exception message: '.$this->getMessage().".\n";
        echo "Stack trace:\n";
        $trace = $this->getTrace();

        if (count($trace) == 0) {
            echo "(No Trace)\n";
        } else {
            $index = '0';

            foreach ($trace as $entry) {
                echo '#'.$index.' '.$entry."\n";
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
     * @return bool The method will always return false.
     */
    public function isShutdownHandler(): bool {
        return false;
    }
}
