<?php
namespace WebFiori\Error;

/**
 * The default exceptions handler with CLI and HTTP awareness.
 * 
 * This handler provides exception output formatting that automatically
 * adapts to both the environment (development, staging, production) and
 * the execution context (CLI vs HTTP). It displays:
 * - Exception location (class and line) - sanitized based on environment level
 * - Exception message - automatically filtered for sensitive information
 * - Stack trace - filtered and limited based on environment
 * 
 * The output format automatically adapts:
 * - CLI: Plain text with ANSI colors and formatting
 * - HTTP: HTML with CSS classes for styling
 * 
 * All content is automatically sanitized to prevent XSS and information disclosure.
 * 
 * Features:
 * - Automatic CLI/HTTP detection and formatting
 * - Automatic path sanitization
 * - Sensitive information filtering
 * - Environment-aware output levels
 * - CSP-compliant HTML output
 * - ANSI color support for CLI
 *
 * @author Ibrahim
 */
class DefaultHandler extends AbstractHandler {
    private $isCli = null;
    
    /**
     * Creates new instance of the class.
     */
    public function __construct() {
        parent::__construct();
        $this->setName('Default');
        
        // Only auto-detect CLI if not already set
        if ($this->isCli === null) {
            $this->setIsCLI(http_response_code() === false);
        }
    }
    
    public function isCLI(): bool {
        return $this->isCli ?? false;
    }
    
    public function setIsCLI(bool $bool): void {
        $this->isCli = $bool;
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
     * Output the opening container (HTML or CLI format).
     */
    private function outputExceptionHeader(): void {
        if ($this->isCLI()) {
            // CLI format - use ANSI colors and plain text
            $this->secureOutput("\n" . str_repeat('-', 60) . "\n");
            $this->secureOutput("\033[1;31mApplication Error\033[0m\n");
            $this->secureOutput(str_repeat('-', 60) . "\n");
        } else {
            // HTML format
            if ($this->getSecurityConfig()->allowInlineStyles()) {
                $this->secureOutput('<div style="border: 1px solid #dc3545; background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 4px; font-family: monospace;">');
            } else {
                $this->secureOutput('<div class="error-container">');
            }
            
            $this->secureOutput('<h3 class="error-title">Application Error</h3>');
        }
    }
    
    /**
     * Output the exception details (location and message) in CLI or HTML format.
     */
    private function outputExceptionDetails(): void {
        if ($this->isCLI()) {
            // CLI format
            $this->outputCLIDetails();
        } else {
            // HTML format
            $this->outputHTMLDetails();
        }
    }
    
    /**
     * Output exception details in CLI format.
     */
    private function outputCLIDetails(): void {
        // Show location information based on environment level
        if ($this->getSecurityConfig()->shouldShowFullPaths() || !$this->isSecureEnvironment()) {
            $this->secureOutput(sprintf(
                "\033[1mLocation:\033[0m %s line %s\n",
                $this->getClass(),
                $this->getLine()
            ));
        } else {
            $this->secureOutput("\033[1mLocation:\033[0m Application Code\n");
        }
        
        // Show message (automatically sanitized)
        $message = $this->getMessage();
        if (!empty($message) && $message !== 'No Message') {
            if ($this->isSecureEnvironment()) {
                $this->secureOutput("\033[1mDetails:\033[0m An error occurred during processing.\n");
            } else {
                $this->secureOutput(sprintf(
                    "\033[1mMessage:\033[0m %s\n",
                    $message
                ));
            }
        }
        
        // Show error code if available
        $code = $this->getCode();
        if ($code !== '0') {
            $this->secureOutput(sprintf(
                "\033[1mCode:\033[0m %s\n",
                $code
            ));
        }
    }
    
    /**
     * Output exception details in HTML format.
     */
    private function outputHTMLDetails(): void {
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
     * Output the formatted stack trace in CLI or HTML format.
     */
    private function outputStackTrace(): void {
        $trace = $this->getTrace();
        
        if (empty($trace)) {
            if (!$this->isSecureEnvironment()) {
                if ($this->isCLI()) {
                    $this->secureOutput("\n\033[2mNo stack trace available\033[0m\n");
                } else {
                    $this->secureOutput('<p><em>No stack trace available</em></p>');
                }
            }
            return;
        }
        
        if ($this->isSecureEnvironment()) {
            // In production, don't show stack trace
            return;
        }
        
        if ($this->isCLI()) {
            $this->outputCLIStackTrace($trace);
        } else {
            $this->outputHTMLStackTrace($trace);
        }
    }
    
    /**
     * Output stack trace in CLI format.
     */
    private function outputCLIStackTrace(array $trace): void {
        $this->secureOutput("\n\033[1mStack Trace:\033[0m\n");
        $this->secureOutput(str_repeat('-', 40) . "\n");
        
        foreach ($trace as $index => $entry) {
            $this->secureOutput(sprintf("#%d %s\n", $index, (string)$entry));
        }
        
        $this->secureOutput(str_repeat('-', 40) . "\n");
    }
    
    /**
     * Output stack trace in HTML format.
     */
    private function outputHTMLStackTrace(array $trace): void {
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
     * Output the closing container and additional information in CLI or HTML format.
     */
    private function outputExceptionFooter(): void {
        if ($this->isCLI()) {
            $this->outputCLIFooter();
        } else {
            $this->outputHTMLFooter();
        }
    }
    
    /**
     * Output footer in CLI format.
     */
    private function outputCLIFooter(): void {
        $this->secureOutput("\n");
        
        // Add helpful information based on environment
        if ($this->isSecureEnvironment()) {
            $this->secureOutput("\033[2mIf this problem persists, please contact support with the error code above.\033[0m\n");
        } else {
            $this->secureOutput("\033[2mThis detailed error information is shown because you are in development mode.\033[0m\n");
        }
        
        // Add timestamp
        $this->secureOutput(sprintf(
            "\033[2mTime: %s\033[0m\n",
            date('Y-m-d H:i:s')
        ));
        
        $this->secureOutput(str_repeat('-', 60) . "\n");
    }
    
    /**
     * Output footer in HTML format.
     */
    private function outputHTMLFooter(): void {
        // Add helpful information based on environment
        if ($this->isSecureEnvironment()) {
            $this->secureOutput('<p class="error-help">If this problem persists, please contact support with the error code above.</p>');
        } else {
            $this->secureOutput('<p class="error-help">This detailed error information is shown because you are in development mode.</p>');
        }
        
        // Add timestamp
        $this->secureOutput(sprintf(
            '<p class="error-timestamp">Time: %s</p>',
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
        
        $this->secureLog('Exception handled by: '.$this->getName(), $context);
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
