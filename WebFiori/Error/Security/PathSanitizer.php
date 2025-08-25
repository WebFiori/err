<?php
namespace WebFiori\Error\Security;

/**
 * Sanitizes file paths to prevent information disclosure.
 * 
 * @author Ibrahim
 */
class PathSanitizer {
    private SecurityConfig $config;
    
    public function __construct(SecurityConfig $config) {
        $this->config = $config;
    }
    
    /**
     * Sanitize a file path based on security configuration.
     */
    public function sanitizePath(string $path): string {
        if ($this->config->shouldShowFullPaths()) {
            return $path;
        }
        
        if ($this->config->isProduction()) {
            return $this->sanitizeForProduction($path);
        }
        
        if ($this->config->isStaging()) {
            return $this->sanitizeForStaging($path);
        }
        
        return $path;
    }
    
    /**
     * Sanitize class name extracted from file path.
     */
    public function sanitizeClassName(string $className): string {
        // In production, remove namespace for brevity and security
        if ($this->config->isProduction()) {
            $parts = explode('\\', $className);
            return end($parts);
        }
        
        return $className;
    }
    
    /**
     * Sanitize path for production environment.
     */
    private function sanitizeForProduction(string $path): string {
        // Show only filename in production
        return basename($path);
    }
    
    /**
     * Sanitize path for staging environment.
     */
    private function sanitizeForStaging(string $path): string {
        $projectRoot = $this->config->getProjectRoot();
        
        // Show relative path from project root
        if (!empty($projectRoot) && str_starts_with($path, $projectRoot)) {
            return '...' . substr($path, strlen($projectRoot));
        }
        
        // If not in project, show only filename
        return basename($path);
    }
    
    /**
     * Check if a path contains sensitive information.
     */
    public function isSensitivePath(string $path): bool {
        $sensitivePatterns = [
            '/\/\.env/',
            '/\/config\/database/',
            '/\/vendor\//',
            '/\/node_modules\//',
            '/\/\.git\//',
            '/\/storage\//',
            '/\/cache\//',
            '/\/tmp\//',
            '/\/temp\//',
        ];
        
        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }
        
        return false;
    }
}
