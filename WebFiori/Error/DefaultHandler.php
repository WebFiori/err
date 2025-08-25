<?php
namespace WebFiori\Error;

/**
 * The default exceptions handler.
 * 
 * This handler provides basic exception output formatting suitable for
 * development environments. It displays:
 * - Exception location (class and line)
 * - Exception message
 * - Complete stack trace
 * 
 * The output is formatted as HTML with <pre> tags for better readability
 * in web browsers, but also works in CLI environments.
 * 
 * Note: This handler should not be used in production environments as it
 * may expose sensitive information.
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
     * Handles the exception by outputting formatted error information.
     * 
     * The output includes:
     * - Exception location (class and line number)
     * - Exception message
     * - Complete stack trace with numbered entries
     * 
     * Output is wrapped in HTML <pre> tags for better formatting.
     */
    public function handle(): void {
        $this->outputExceptionHeader();
        $this->outputExceptionDetails();
        $this->outputStackTrace();
        $this->outputExceptionFooter();
    }
    
    /**
     * Output the opening HTML tag.
     */
    private function outputExceptionHeader(): void {
        echo '<pre>' . "\n";
    }
    
    /**
     * Output the exception details (location and message).
     */
    private function outputExceptionDetails(): void {
        echo sprintf(
            "An exception was thrown at %s line %s.\n",
            $this->getClass(),
            $this->getLine()
        );
        
        echo sprintf(
            "Exception message: %s.\n",
            $this->getMessage()
        );
    }
    
    /**
     * Output the formatted stack trace.
     */
    private function outputStackTrace(): void {
        echo "Stack trace:\n";
        $trace = $this->getTrace();

        if (count($trace) === 0) {
            echo "(No Trace)\n";
            return;
        }

        foreach ($trace as $index => $entry) {
            echo sprintf("#%d %s\n", $index, $entry);
        }
    }
    
    /**
     * Output the closing HTML tag.
     */
    private function outputExceptionFooter(): void {
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
