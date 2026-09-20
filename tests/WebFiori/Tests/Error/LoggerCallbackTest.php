<?php
namespace WebFiori\Tests\Error;

use PHPUnit\Framework\TestCase;
use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Handler;

/**
 * Tests for the logging-via-callback feature (issue #13).
 *
 * @author Ibrahim
 */
class LoggerCallbackTest extends TestCase {
    protected function setUp(): void {
        Handler::reset();
        Handler::setDefaultLogCallback(null);
    }

    protected function tearDown(): void {
        Handler::reset();
        Handler::setDefaultLogCallback(null);
        restore_error_handler();
    }

    private function makeHandler(string $name = 'LogTestHandler'): AbstractHandler {
        $h = new class extends AbstractHandler {
            public function __construct() {
                parent::__construct();
                $this->setName('LogTestHandler');
            }
            public function handle(): void {
            }
            public function isActive(): bool {
                return true;
            }
            public function isShutdownHandler(): bool {
                return false;
            }
        };
        $h->setName($name);

        return $h;
    }

    /**
     * @test
     */
    public function testSecureLogRoutesThroughInstanceCallback(): void {
        $captured = [];
        $handler = $this->makeHandler();
        $handler->setLogCallback(function (string $level, string $message, array $context) use (&$captured) {
            $captured[] = [$level, $message, $context];
        });

        $handler->secureLog('Something happened', ['code' => 42]);

        $this->assertCount(1, $captured);
        $this->assertSame('error', $captured[0][0]);
        $this->assertSame('Something happened', $captured[0][1]);
        $this->assertSame(42, $captured[0][2]['code']);
        $this->assertSame('LogTestHandler', $captured[0][2]['handler']);
    }

    /**
     * @test
     */
    public function testSecureLogFallsBackToGlobalCallback(): void {
        $captured = [];
        Handler::setDefaultLogCallback(function (string $level, string $message, array $context) use (&$captured) {
            $captured[] = [$level, $message, $context];
        });

        $handler = $this->makeHandler();
        // No instance callback set — global should be used.
        $handler->secureLog('Global route');

        $this->assertCount(1, $captured);
        $this->assertSame('Global route', $captured[0][1]);
    }

    /**
     * @test
     */
    public function testInstanceCallbackOverridesGlobal(): void {
        $global = [];
        $instance = [];
        Handler::setDefaultLogCallback(function ($l, $m, $c) use (&$global) {
            $global[] = $m;
        });

        $handler = $this->makeHandler();
        $handler->setLogCallback(function ($l, $m, $c) use (&$instance) {
            $instance[] = $m;
        });

        $handler->secureLog('Prefer instance');

        $this->assertSame(['Prefer instance'], $instance);
        $this->assertSame([], $global);
    }

    /**
     * @test
     */
    public function testGetLogCallbackReturnsConfiguredCallback(): void {
        $handler = $this->makeHandler();
        $this->assertNull($handler->getLogCallback());

        $cb = function () {
        };
        $handler->setLogCallback($cb);
        $this->assertSame($cb, $handler->getLogCallback());

        $handler->setLogCallback(null);
        $this->assertNull($handler->getLogCallback());
    }

    /**
     * @test
     */
    public function testDefaultLogCallbackAccessors(): void {
        $this->assertNull(Handler::getDefaultLogCallback());

        $cb = function () {
        };
        Handler::setDefaultLogCallback($cb);
        $this->assertSame($cb, Handler::getDefaultLogCallback());
    }

    /**
     * @test
     * The global callback survives Handler::reset() because it is
     * application-level configuration, not per-request handler state.
     */
    public function testGlobalCallbackPersistsAcrossReset(): void {
        $cb = function () {
        };
        Handler::setDefaultLogCallback($cb);
        Handler::reset();
        $this->assertSame($cb, Handler::getDefaultLogCallback());
    }

    /**
     * @test
     * Context is passed through the sanitizer before reaching the callback.
     */
    public function testSecureLogSanitizesBeforeCallback(): void {
        $capturedMessage = '';
        $handler = $this->makeHandler();
        $handler->setConfig(new \WebFiori\Error\Config\HandlerConfig());
        $handler->setLogCallback(function ($l, string $m, array $c) use (&$capturedMessage) {
            $capturedMessage = $m;
        });

        // Inline credentials in the message are redacted by the sanitizer
        // before the callback receives the message.
        $handler->secureLog('db password=super-secret-value connecting');

        $this->assertStringNotContainsString('super-secret-value', $capturedMessage);
        $this->assertStringContainsString('[REDACTED]', $capturedMessage);
    }
}
