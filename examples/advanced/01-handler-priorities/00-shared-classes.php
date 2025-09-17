<?php
/**
 * Shared Classes for Handler Priority Examples
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Error\AbstractHandler;

abstract class PriorityDemoHandler extends AbstractHandler {
    protected int $executionOrder = 0;
    protected static int $globalExecutionCounter = 0;
    
    public function handle(): void {
        self::$globalExecutionCounter++;
        $this->executionOrder = self::$globalExecutionCounter;
        
        echo sprintf(
            "[%d] %s (Priority: %d) executed\n",
            $this->executionOrder,
            $this->getName(),
            $this->getPriority()
        );
    }
    
    public function isActive(): bool { return true; }
    public function isShutdownHandler(): bool { return false; }
    public function getExecutionOrder(): int { return $this->executionOrder; }
    public static function resetCounter(): void { self::$globalExecutionCounter = 0; }
}
