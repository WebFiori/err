<?php
namespace WebFiori\Error\Security;

use WebFiori\Error\TraceEntry;

/**
 * Filters and sanitizes stack traces based on security configuration.
 * 
 * @author Ibrahim
 */
class StackTraceFilter {
    private SecurityConfig $config;
    private PathSanitizer $pathSanitizer;
    
    public function __construct(SecurityConfig $config, PathSanitizer $pathSanitizer) {
        $this->config = $config;
        $this->pathSanitizer = $pathSanitizer;
    }
    
    /**
     * Filter stack trace based on configuration.
     * Optimized for performance with deep call stacks.
     */
    public function filterTrace(array $trace): array {
        if (!$this->config->shouldShowStackTrace()) {
            return [];
        }
        
        $maxDepth = $this->config->getMaxTraceDepth();
        if ($maxDepth <= 0) {
            return [];
        }
        
        $filtered = [];
        $count = 0;
        
        // Process entries efficiently, stopping early when limits are reached
        foreach ($trace as $entry) {
            if ($count >= $maxDepth) {
                break;
            }
            
            if ($this->shouldIncludeTraceEntry($entry)) {
                $filtered[] = $this->sanitizeTraceEntry($entry);
                $count++;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Check if a trace entry should be included.
     */
    private function shouldIncludeTraceEntry(TraceEntry $entry): bool {
        $file = $entry->getFile();
        
        // Skip vendor files in production
        if ($this->config->isProduction() && str_contains($file, '/vendor/')) {
            return false;
        }
        
        // Skip other sensitive paths (but not vendor in dev/staging)
        $nonVendorSensitivePaths = [
            '/\/\.env/',
            '/\/config\/database/',
            '/\/\.git\//',
            '/\/storage\//',
            '/\/cache\//',
            '/\/tmp\//',
            '/\/temp\//',
        ];
        
        foreach ($nonVendorSensitivePaths as $pattern) {
            if (preg_match($pattern, $file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize a trace entry.
     */
    private function sanitizeTraceEntry(TraceEntry $entry): TraceEntry {
        // Create a new sanitized trace entry
        $sanitizedData = [
            'file' => $this->pathSanitizer->sanitizePath($entry->getFile()),
            'line' => $this->config->shouldShowLineNumbers() ? $entry->getLine() : '(Hidden)',
            'class' => $this->pathSanitizer->sanitizeClassName($entry->getClass()),
            'function' => $this->sanitizeMethodName($entry->getMethod())
        ];
        
        return new TraceEntry($sanitizedData);
    }
    
    /**
     * Sanitize method names to hide sensitive methods.
     */
    private function sanitizeMethodName(string $methodName): string {
        if ($this->config->isProduction()) {
            $sensitiveMethods = ['password', 'auth', 'login', 'token', 'secret', 'credential'];
            
            foreach ($sensitiveMethods as $sensitive) {
                if (stripos($methodName, $sensitive) !== false) {
                    return '[SENSITIVE_METHOD]';
                }
            }
        }
        
        return $methodName;
    }
}
