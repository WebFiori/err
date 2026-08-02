<?php
namespace WebFiori\Error\Security;

use WebFiori\Error\AbstractHandler;

/**
 * Monitors security violations and handler execution.
 * 
 * @author Ibrahim
 */
class SecurityMonitor {
    private SecurityConfig $config;
    private array $violations = [];
    private array $handlerExecutions = [];
    
    public function __construct(SecurityConfig $config) {
        $this->config = $config;
    }
    
    /**
     * Record a security violation.
     */
    public function recordSecurityViolation(string $violation, AbstractHandler $handler): void {
        $this->violations[] = [
            'violation' => $violation,
            'handler' => $handler->getName(),
            'timestamp' => time(),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
        
        if ($this->config->get('log_security_violations', true)) {
            $this->logSecurityViolation($violation, $handler);
        }
    }
    
    /**
     * Record handler execution for monitoring.
     */
    public function recordHandlerExecution(AbstractHandler $handler): void {
        $this->handlerExecutions[] = [
            'handler' => $handler->getName(),
            'timestamp' => time(),
            'memory_usage' => memory_get_usage(true)
        ];
    }
    
    /**
     * Get all recorded violations.
     */
    public function getViolations(): array {
        return $this->violations;
    }
    
    /**
     * Get handler execution statistics.
     */
    public function getExecutionStats(): array {
        return $this->handlerExecutions;
    }
    
    /**
     * Log security violation to error log.
     */
    private function logSecurityViolation(string $violation, AbstractHandler $handler): void {
        $logEntry = [
            'type' => 'SECURITY_VIOLATION',
            'violation' => $violation,
            'handler' => $handler->getName(),
            'timestamp' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip' => $this->getClientIp(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
        ];
        
        error_log('WebFiori Security Violation: ' . json_encode($logEntry));
    }
    
    /**
     * Get client IP address safely.
     */
    private function getClientIp(): string {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Take first IP if comma-separated
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return 'Unknown';
    }
}
