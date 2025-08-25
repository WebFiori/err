<?php
namespace WebFiori\Error\Security;

/**
 * Sanitizes output to prevent information disclosure and XSS attacks.
 * 
 * @author Ibrahim
 */
class OutputSanitizer {
    private SecurityConfig $config;
    private array $sensitivePatterns;
    private array $credentialPatterns;
    
    public function __construct(SecurityConfig $config) {
        $this->config = $config;
        $this->initializePatterns();
    }
    
    /**
     * Initialize sensitive data patterns.
     */
    private function initializePatterns(): void {
        $this->sensitivePatterns = [
            // Credentials and secrets
            '/password["\s]*[:=]["\s]*[^"\s\n]+/i',
            '/secret["\s]*[:=]["\s]*[^"\s\n]+/i',
            '/token["\s]*[:=]["\s]*[^"\s\n]+/i',
            '/api[_-]?key["\s]*[:=]["\s]*[^"\s\n]+/i',
            '/private[_-]?key["\s]*[:=]["\s]*[^"\s\n]+/i',
            
            // Database connections
            '/mysql:\/\/[^@]+@[^\/]+/i',
            '/postgres:\/\/[^@]+@[^\/]+/i',
            '/mongodb:\/\/[^@]+@[^\/]+/i',
            
            // Session IDs
            '/PHPSESSID["\s]*[:=]["\s]*[a-zA-Z0-9]+/i',
            '/session[_-]?id["\s]*[:=]["\s]*[a-zA-Z0-9]+/i',
            
            // Internal IP addresses
            '/\b(?:10\.|172\.(?:1[6-9]|2[0-9]|3[01])\.|192\.168\.)\d{1,3}\.\d{1,3}\b/',
        ];
        
        $this->credentialPatterns = [
            // JSON format
            '/"(?:password|secret|token|key)":\s*"[^"]+"/i',
            // Array format
            '/\[[\'"](password|secret|token|key)[\'"]\]\s*=>\s*[\'"][^\'"]+[\'"]/i',
            // Environment variable format
            '/(?:PASSWORD|SECRET|TOKEN|KEY)=[^\s\n]+/i',
        ];
    }
    
    /**
     * Sanitize HTML output for safe display.
     */
    public function sanitize(string $content): string {
        // First sanitize for sensitive information
        $sanitized = $this->sanitizeMessage($content);
        
        // Then sanitize HTML
        $sanitized = $this->sanitizeHtml($sanitized);
        
        // Make CSP compliant
        $sanitized = $this->makeCSPCompliant($sanitized);
        
        return $sanitized;
    }
    
    /**
     * Sanitize exception messages and text content.
     */
    public function sanitizeMessage(string $message): string {
        // Always filter critical sensitive information regardless of environment
        $criticalPatterns = [
            '/password["\s]*[:=]["\s]*([^"\s\n]+)/i',
            '/token["\s]*[:=]["\s]*([^"\s\n]+)/i',
        ];
        
        $sanitized = $message;
        foreach ($criticalPatterns as $pattern) {
            $sanitized = preg_replace_callback($pattern, function($matches) {
                $prefix = str_replace($matches[1], '', $matches[0]);
                return $prefix . '[REDACTED]';
            }, $sanitized);
        }
        
        // Apply additional sanitization based on environment
        if ($this->config->get('sanitize_messages', false)) {
            if ($this->config->isProduction()) {
                $sanitized = $this->sanitizeForProduction($sanitized);
            } elseif ($this->config->isStaging()) {
                $sanitized = $this->sanitizeForStaging($sanitized);
            }
        }
        
        // Apply length limit
        $maxLength = $this->config->getMaxMessageLength();
        if ($maxLength > 0 && strlen($sanitized) > $maxLength) {
            $sanitized = substr($sanitized, 0, $maxLength) . '... [TRUNCATED]';
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize context arrays for logging.
     */
    public function sanitizeContext(array $context): array {
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            $sanitizedKey = $this->sanitizeKey($key);
            $sanitizedValue = $this->sanitizeValue($value);
            $sanitized[$sanitizedKey] = $sanitizedValue;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize for production environment.
     */
    private function sanitizeForProduction(string $message): string {
        $sanitized = $message;
        
        // Remove all sensitive patterns
        foreach ($this->sensitivePatterns as $pattern) {
            $sanitized = preg_replace($pattern, '[REDACTED]', $sanitized);
        }
        
        // Remove credential patterns
        foreach ($this->credentialPatterns as $pattern) {
            $sanitized = preg_replace($pattern, '[CREDENTIALS]', $sanitized);
        }
        
        // Remove SQL queries
        $sanitized = preg_replace('/SELECT\s+.*?\s+FROM\s+\w+/i', '[SQL_QUERY]', $sanitized);
        $sanitized = preg_replace('/INSERT\s+INTO\s+.*?\s+VALUES\s*\([^)]+\)/i', '[SQL_INSERT]', $sanitized);
        $sanitized = preg_replace('/UPDATE\s+\w+\s+SET\s+.*?\s+WHERE/i', '[SQL_UPDATE]', $sanitized);
        
        return $sanitized;
    }
    
    /**
     * Sanitize for staging environment.
     */
    private function sanitizeForStaging(string $message): string {
        $sanitized = $message;
        
        // Remove credentials but keep some context
        foreach ($this->sensitivePatterns as $pattern) {
            $sanitized = preg_replace_callback($pattern, function($matches) {
                $parts = preg_split('/[:=]/', $matches[0], 2);
                $key = trim($parts[0] ?? 'SENSITIVE');
                return $key . ': [REDACTED]';
            }, $sanitized);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize for development environment.
     */
    private function sanitizeForDevelopment(string $message): string {
        $sanitized = $message;
        
        // Only remove actual passwords and tokens, keep structure
        $criticalPatterns = [
            '/password["\s]*[:=]["\s]*([^"\s\n]+)/i',
            '/token["\s]*[:=]["\s]*([^"\s\n]+)/i',
        ];
        
        foreach ($criticalPatterns as $pattern) {
            $sanitized = preg_replace_callback($pattern, function($matches) {
                $prefix = str_replace($matches[1], '', $matches[0]);
                return $prefix . '[DEV_REDACTED]';
            }, $sanitized);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize HTML content.
     */
    private function sanitizeHtml(string $content): string {
        // Allow only safe HTML tags
        $allowedTags = '<div><p><span><pre><code><br><strong><em><h1><h2><h3><h4><h5><h6><ul><ol><li><details><summary>';
        
        $sanitized = strip_tags($content, $allowedTags);
        
        // Remove dangerous attributes
        $sanitized = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/', '', $sanitized);
        $sanitized = preg_replace('/\s*javascript\s*:\s*[^"\'>\s]+/', '', $sanitized);
        $sanitized = preg_replace('/\s*data\s*:\s*[^"\'>\s]+/', '', $sanitized);
        
        return $sanitized;
    }
    
    /**
     * Make content CSP compliant.
     */
    private function makeCSPCompliant(string $content): string {
        if (!$this->config->allowInlineStyles()) {
            // Remove inline styles
            $content = preg_replace('/\s*style\s*=\s*["\'][^"\']*["\']/', '', $content);
            
            // Add CSS classes instead
            $content = str_replace('<div>', '<div class="error-container">', $content);
            $content = str_replace('<pre>', '<pre class="error-trace">', $content);
        }
        
        return $content;
    }
    
    /**
     * Sanitize array keys.
     */
    private function sanitizeKey(string $key): string {
        // In production, be more aggressive with key sanitization
        if ($this->config->isProduction()) {
            $sensitiveKeys = ['password', 'secret', 'token', 'key', 'auth', 'credential'];
            
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    return '[SENSITIVE_KEY]';
                }
            }
        }
        
        return $key;
    }
    
    /**
     * Sanitize values of various types.
     */
    private function sanitizeValue($value) {
        if (is_string($value)) {
            return $this->sanitizeMessage($value);
        }
        
        if (is_array($value)) {
            return $this->sanitizeContext($value);
        }
        
        if (is_object($value)) {
            return $this->sanitizeObject($value);
        }
        
        return $value;
    }
    
    /**
     * Sanitize object values.
     */
    private function sanitizeObject($object): array {
        if ($object instanceof \Throwable) {
            return [
                'class' => get_class($object),
                'message' => $this->sanitizeMessage($object->getMessage()),
                'code' => $object->getCode()
            ];
        }
        
        // Convert other objects to array and sanitize
        $array = (array) $object;
        return $this->sanitizeContext($array);
    }
}
