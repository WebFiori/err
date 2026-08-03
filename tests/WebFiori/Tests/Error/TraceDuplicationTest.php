<?php

namespace WebFiori\Tests\Error;

use Exception;
use PHPUnit\Framework\TestCase;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Security\SecurityConfig;

/**
 * Test for issue #15: Stack trace duplication when setException() is called multiple times.
 */
class TraceDuplicationTest extends TestCase {
    /**
     * @test
     * Verify that calling setException() twice does not duplicate trace entries.
     */
    public function testNoTraceDuplicationOnMultipleSetExceptionCalls() {
        $handler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('DuplicationTestHandler');
            }

            public function handle(): void {}
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }

            public function createSecurityConfig(): SecurityConfig {
                return new SecurityConfig(SecurityConfig::LEVEL_DEV);
            }

            public function exposeRawTrace(): array {
                return $this->getRawTrace();
            }
        };

        $ex = new Exception('Test error');

        // Call setException twice (simulates the double-call in Handler.php)
        $handler->setException($ex);
        $firstTrace = $handler->exposeRawTrace();
        $firstCount = count($firstTrace);

        $handler->setException($ex);
        $secondTrace = $handler->exposeRawTrace();
        $secondCount = count($secondTrace);

        // Trace should be identical size — no duplication from repeated calls
        $this->assertEquals($firstCount, $secondCount,
            'Trace should not grow when setException() is called multiple times.'
            . " First call: $firstCount entries, second call: $secondCount entries.");
    }

    /**
     * @test
     * Verify that calling setException() three times still produces same trace size.
     */
    public function testNoTraceDuplicationOnTripleSetExceptionCalls() {
        $handler = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('TripleDuplicationHandler');
            }

            public function handle(): void {}
            public function isActive(): bool { return true; }
            public function isShutdownHandler(): bool { return false; }

            public function createSecurityConfig(): SecurityConfig {
                return new SecurityConfig(SecurityConfig::LEVEL_DEV);
            }

            public function exposeRawTrace(): array {
                return $this->getRawTrace();
            }
        };

        $ex = new Exception('Test error');

        $handler->setException($ex);
        $firstCount = count($handler->exposeRawTrace());

        $handler->setException($ex);
        $handler->setException($ex);
        $thirdCount = count($handler->exposeRawTrace());

        $this->assertEquals($firstCount, $thirdCount,
            'Trace count should remain stable regardless of how many times setException() is called.');
    }
}
