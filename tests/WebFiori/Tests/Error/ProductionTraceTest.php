<?php

namespace WebFiori\Tests\Error;

use Exception;
use PHPUnit\Framework\TestCase;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Security\SecurityConfig;

/**
 * Test for issue #17: getException() returning null breaks trace building in production.
 */
class ProductionTraceTest extends TestCase {
    /**
     * @test
     * Verifies that the raw trace is built even in production mode.
     */
    public function testTraceBuildInProductionMode() {
        $handler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('ProdTraceTestHandler');
            }

            public function handle(): void {}
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }

            public function createSecurityConfig(): SecurityConfig {
                return new SecurityConfig(SecurityConfig::LEVEL_PROD);
            }

            // Expose protected method for testing
            public function exposeRawTrace(): array {
                return $this->getRawTrace();
            }
        };

        // In production, getException() should return null (security gate)
        $this->assertNull($handler->getException());

        // Set an exception — this triggers setTrace() internally
        $handler->setException(new Exception('Test error'));

        // getException() should still return null in production
        $this->assertNull($handler->getException());

        // The RAW trace should be built regardless of security level
        $rawTrace = $handler->exposeRawTrace();
        $this->assertNotEmpty($rawTrace, 'Raw trace should be built in production mode');
    }

    /**
     * @test
     * Verifies that getTrace() (filtered) returns empty in production — this is by design.
     * The filter intentionally hides traces from external display in production.
     */
    public function testFilteredTraceEmptyInProductionByDesign() {
        $handler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('FilteredTraceHandler');
            }

            public function handle(): void {}
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }

            public function createSecurityConfig(): SecurityConfig {
                return new SecurityConfig(SecurityConfig::LEVEL_PROD);
            }
        };

        $handler->setException(new Exception('Test error'));

        // getTrace() goes through the filter — empty in production is correct
        $this->assertEmpty($handler->getTrace());
    }

    /**
     * @test
     * Verifies that trace works in development mode (baseline).
     */
    public function testTraceBuildInDevelopmentMode() {
        $handler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('DevTraceTestHandler');
            }

            public function handle(): void {}
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }

            public function createSecurityConfig(): SecurityConfig {
                return new SecurityConfig(SecurityConfig::LEVEL_DEV);
            }
        };

        $handler->setException(new Exception('Test error'));

        // In dev mode, both raw and filtered should work
        $this->assertNotNull($handler->getException());
        $this->assertNotEmpty($handler->getTrace());
    }

    /**
     * @test
     * Verifies that production mode still blocks raw exception access.
     */
    public function testProductionStillBlocksRawExceptionAccess() {
        $handler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('SecurityGateHandler');
            }

            public function handle(): void {}
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }

            public function createSecurityConfig(): SecurityConfig {
                return new SecurityConfig(SecurityConfig::LEVEL_PROD);
            }
        };

        $handler->setException(new Exception('Sensitive error details'));

        // Public access to raw exception must remain blocked
        $this->assertNull($handler->getException());

        // But error message (through sanitized accessor) should still work
        $this->assertNotEmpty($handler->getMessage());
    }
}
