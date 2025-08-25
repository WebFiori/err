<?php
namespace WebFiori\Error;

/**
 * The default exceptions handler.
 * 
 * This handler provides exception output formatting that automatically
 * adapts to the environment (development, staging, production). It displays:
 * - Exception location (class and line) - sanitized based on environment level
 * - Exception message - automatically filtered for sensitive information
 * - Stack trace - filtered and limited based on environment
 * 
 * The output is formatted as HTML with CSS classes for styling, and all
 * content is automatically sanitized to prevent XSS and information disclosure.
 * 
 * Features:
 * - Automatic path sanitization
 * - Sensitive information filtering
 * - Environment-aware output levels
 * - CSP-compliant HTML output
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
     * The output automatically adapts based on the environment:
     * - Production: Minimal information, no sensitive data
     * - Staging: Limited information with sanitized paths
     * - Development: Full information with sensitive data filtered
     */
    public function handle(): void {
        $this->outputExceptionHeader();
        $this->outputExceptionDetails();
        $this->outputStackTrace();
        $this->outputExceptionFooter();
        
        // Log the exception securely
        $this->logException();
    }
    
    /**
     * Output the opening HTML container.
     */
    private function outputExceptionHeader(): void {
        if ($this->getSecurityConfig()->allowInlineStyles()) {
            $this->secureOutput('<div style="border: 1px solid #dc3545; background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px; font-family: monospace;">');
        } else {
            $this->secureOutput('<div class="error-container">');
        }
        
        $this->secureOutput('<h3 class="error-title">Application Error</h3>');
    }
    
    /**
     * Output the exception details (location and message).
     */
    private function outputExceptionDetails(): void {
        // Show location information based on environment level
        if ($this->getSecurityConfig()->shouldShowFullPaths() || !$this->isSecureEnvironment()) {
            $this->secureOutput(sprintf(
                '<p><strong>Location:</strong> %s line %s</p>',
                htmlspecialchars($this->getClass()),
                htmlspecialchars($this->getLine())
            ));
        } else {
            $this->secureOutput('<p><strong>Location:</strong> Application code</p>');
        }
        
        // Show message (automatically sanitized)
        $message = $this->getMessage();
        if (!empty($message) && $message !== 'No Message') {
            if ($this->isSecureEnvironment()) {
                $this->secureOutput('<p><strong>Details:</strong> An error occurred during processing.</p>');
            } else {
                $this->secureOutput(sprintf(
                    '<p><strong>Message:</strong> %s</p>',
                    htmlspecialchars($message)
                ));
            }
        }
        
        // Show error code if available
        $code = $this->getCode();
        if ($code !== '0') {
            $this->secureOutput(sprintf(
                '<p><strong>Code:</strong> %s</p>',
                htmlspecialchars($code)
            ));
        }
    }
    
    /**
     * Output the formatted stack trace.
     */
    private function outputStackTrace(): void {
        $trace = $this->getTrace();
        
        if (empty($trace)) {
            if (!$this->isSecureEnvironment()) {
                $this->secureOutput('<p><em>No stack trace available</em></p>');
            }
            return;
        }
        
        if ($this->isSecureEnvironment()) {
            // In production, don't show stack trace
            return;
        }
        
        $this->secureOutput('<details class="error-trace">');
        $this->secureOutput('<summary><strong>Stack Trace</strong></summary>');
        
        if ($this->getSecurityConfig()->allowInlineStyles()) {
            $this->secureOutput('<pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;">');
        } else {
            $this->secureOutput('<pre class="error-trace-content">');
        }
        
        foreach ($trace as $index => $entry) {
            $this->secureOutput(sprintf("#%d %s\n", $index, htmlspecialchars((string)$entry)));
        }
        
        $this->secureOutput('</pre>');
        $this->secureOutput('</details>');
    }
    
    /**
     * Output the closing HTML container and additional information.
     */
    private function outputExceptionFooter(): void {
        // Add helpful information based on environment
        if ($this->isSecureEnvironment()) {
            $this->secureOutput('<p class="error-help"><small>If this problem persists, please contact support with the error code above.</small></p>');
        } else {
            $this->secureOutput('<p class="error-help"><small>This detailed error information is shown because you are in development mode.</small></p>');
        }
        
        // Add timestamp
        $this->secureOutput(sprintf(
            '<p class="error-timestamp"><small>Time: %s</small></p>',
            date('Y-m-d H:i:s')
        ));
        
        $this->secureOutput('</div>');
    }
    
    /**
     * Log the exception with context information.
     */
    private function logException(): void {
        $context = [
            'class' => $this->getClass(),
            'line' => $this->getLine(),
            'code' => $this->getCode(),
            'environment' => $this->getSecurityConfig()->getSecurityLevel(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
        ];
        
        // Add stack trace in development
        if ($this->getSecurityConfig()->isDevelopment()) {
            $context['trace'] = array_map(function($entry) {
                return (string)$entry;
            }, $this->getTrace());
        }
        
        $this->secureLog('Exception handled by DefaultHandler', $context);
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
