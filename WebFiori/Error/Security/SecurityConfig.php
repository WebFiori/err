<?php
namespace WebFiori\Error\Security;

/**
 * Security configuration class that manages security settings based on environment.
 * 
 * @author Ibrahim
 */
class SecurityConfig {
    const LEVEL_DEV = 'dev';
    const LEVEL_STAGING = 'staging';
    const LEVEL_PROD = 'prod';
    
    private string $securityLevel;
    private array $config;
    private ?string $projectRoot = null;
    
    public function __construct(?string $level = null) {
        $this->securityLevel = $level ?? $this->detectSecurityLevel();
        $this->loadConfig();
        $this->detectProjectRoot();
    }
    
    /**
     * Detect the current security level based on environment indicators.
     */
    private function detectSecurityLevel(): string {
        // Check environment variables
        $env = getenv('APP_ENV') ?: getenv('ENVIRONMENT');
        if ($env === 'production' || $env === 'prod') {
            return self::LEVEL_PROD;
        }
        if ($env === 'staging') {
            return self::LEVEL_STAGING;
        }
        
        // Check PHP settings
        if (!ini_get('display_errors')) {
            return self::LEVEL_PROD;
        }
        
        // Check for production constants
        if (defined('PRODUCTION') && PRODUCTION === true) {
            return self::LEVEL_PROD;
        }
        
        // Default to development
        return self::LEVEL_DEV;
    }
    
    /**
     * Load configuration based on security level.
     */
    private function loadConfig(): void {
        $this->config = match($this->securityLevel) {
            self::LEVEL_DEV => [
                'show_full_paths' => true,
                'show_stack_trace' => true,
                'max_trace_depth' => 50,
                'show_line_numbers' => true,
                'allow_raw_exception_access' => true,
                'sanitize_messages' => false,
                'max_message_length' => 0,
                'allow_inline_styles' => true,
                'log_security_violations' => true
            ],
            self::LEVEL_STAGING => [
                'show_full_paths' => false,
                'show_stack_trace' => true,
                'max_trace_depth' => 10,
                'show_line_numbers' => true,
                'allow_raw_exception_access' => false,
                'sanitize_messages' => true,
                'max_message_length' => 500,
                'allow_inline_styles' => false,
                'log_security_violations' => true
            ],
            self::LEVEL_PROD => [
                'show_full_paths' => false,
                'show_stack_trace' => false,
                'max_trace_depth' => 0,
                'show_line_numbers' => false,
                'allow_raw_exception_access' => false,
                'sanitize_messages' => true,
                'max_message_length' => 200,
                'allow_inline_styles' => false,
                'log_security_violations' => true
            ]
        };
    }
    
    /**
     * Detect the project root directory.
     */
    private function detectProjectRoot(): void {
        // Try to find composer.json or other indicators
        $currentDir = __DIR__;
        while ($currentDir !== dirname($currentDir)) {
            if (file_exists($currentDir . '/composer.json')) {
                $this->projectRoot = $currentDir;
                return;
            }
            $currentDir = dirname($currentDir);
        }
        
        // Fallback to document root or current directory
        $this->projectRoot = $_SERVER['DOCUMENT_ROOT'] ?? getcwd();
    }
    
    /**
     * Get a configuration value.
     */
    public function get(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Check if we should show full file paths.
     */
    public function shouldShowFullPaths(): bool {
        return $this->get('show_full_paths', false);
    }
    
    /**
     * Check if we should show line numbers.
     */
    public function shouldShowLineNumbers(): bool {
        return $this->get('show_line_numbers', false);
    }
    
    /**
     * Check if we should show stack traces.
     */
    public function shouldShowStackTrace(): bool {
        return $this->get('show_stack_trace', false);
    }
    
    /**
     * Check if raw exception access is allowed.
     */
    public function allowRawExceptionAccess(): bool {
        return $this->get('allow_raw_exception_access', false);
    }
    
    /**
     * Get maximum stack trace depth.
     */
    public function getMaxTraceDepth(): int {
        return $this->get('max_trace_depth', 0);
    }
    
    /**
     * Get maximum message length.
     */
    public function getMaxMessageLength(): int {
        return $this->get('max_message_length', 0);
    }
    
    /**
     * Check if inline styles are allowed.
     */
    public function allowInlineStyles(): bool {
        return $this->get('allow_inline_styles', false);
    }
    
    /**
     * Get the current security level.
     */
    public function getSecurityLevel(): string {
        return $this->securityLevel;
    }
    
    /**
     * Check if we're in development mode.
     */
    public function isDevelopment(): bool {
        return $this->securityLevel === self::LEVEL_DEV;
    }
    
    /**
     * Check if we're in staging mode.
     */
    public function isStaging(): bool {
        return $this->securityLevel === self::LEVEL_STAGING;
    }
    
    /**
     * Check if we're in production mode.
     */
    public function isProduction(): bool {
        return $this->securityLevel === self::LEVEL_PROD;
    }
    
    /**
     * Get the project root directory.
     */
    public function getProjectRoot(): string {
        return $this->projectRoot ?? '';
    }
}
