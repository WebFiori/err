<?php

namespace WebFiori\Tests\Error;

require_once 'SampleHandler1.php';
require_once 'SampleHandler2.php';
require_once 'SampleHandler3.php';

use PHPUnit\Framework\TestCase;
use WebFiori\Error\DefaultHandler;
use WebFiori\Error\ErrorHandlerException;
use WebFiori\Error\Handler;
use const SampleHandler3;
/**
 * Description of HandlerTest
 *
 * @author Ibrahim
 */
class HandlerTest extends TestCase {
    use OutputBufferingTrait;
    
    /**
     * @test
     */
    public function test00() {
        $this->expectException(ErrorHandlerException::class);

        // Match the meaningful parts of the message without pinning the exact
        // source line number (which shifts as the test file changes) or the
        // exact warning wording (which varies across PHP versions).
        if (PHP_MAJOR_VERSION == 7) {
            $pattern = '/Undefined variable: y at HandlerTest Line \d+/';
        } else {
            $pattern = '/An exception caused by an error\. Run-time warning: Undefined variable \$y at HandlerTest Line \d+/';
        }
        $this->expectExceptionMessageMatches($pattern);
        $h = Handler::get();
        // Ensure the handler is active and E_WARNING is reportable so the
        // undefined-variable warning is converted deterministically (PHPUnit
        // may restrict error_reporting during test execution). Restore the
        // previous reporting level afterwards to avoid leaking global state
        // into subsequent tests.
        $h->reset();
        $previousReporting = error_reporting(E_ALL);

        try {
            $x = $y;
        } finally {
            error_reporting($previousReporting);
        }
    }
    /**
     * @test
     */
    public function test01() {
        $h = Handler::get();
        $this->assertFalse($h->hasHandler('H1'));
        $h->registerHandler(new SampleHandler1());
        $this->assertTrue($h->hasHandler('H1'));
        $h->unregisterHandler($h->getHandler('H1'));
        $this->assertFalse($h->hasHandler('H1'));
    }
    /**
     * @test
     */
    public function test02() {
        $h = Handler::get();
        $h->reset();
        $h->registerHandler(new SampleHandler1());
        $this->assertFalse(defined('SampleHandler1'));
        $h->invokeExceptionsHandler();
        $this->assertTrue(defined('SampleHandler1'));
    }
    /**
     * @test
     */
    public function test03() {
        $h = Handler::get();
        $h->reset();
        $h->registerHandler(new SampleHandler2());
        $this->assertFalse(defined('SampleHandler2'));
        $h->invokeExceptionsHandler();
        $this->assertFalse(defined('SampleHandler2'));
        $h->unregisterHandler($h->getHandler('Default'));
        $h->invokeShutdownHandler();
        $this->assertTrue(defined('SampleHandler2'));
    }
    /**
     * @test
     */
    public function test04() {
        $h = Handler::get();
        $this->assertFalse($h->hasHandler('H1'));
        $h->registerHandler(new SampleHandler1());
        $this->assertTrue($h->hasHandler('H1'));
        $h->unregisterHandlerByName('H1');
        $this->assertFalse($h->hasHandler('H1'));
    }
    /**
     * @test
     */
    public function test05() {
        $h = Handler::get();
        $this->assertFalse($h->hasHandler('H1'));
        $h->registerHandler(new SampleHandler1());
        $this->assertTrue($h->hasHandler('H1'));
        $h->unregisterHandlerByName(SampleHandler1::class);
        $this->assertFalse($h->hasHandler('H1'));
    }
    /**
     * @test
     */
    public function testPriority00() {
        $h = Handler::get();
        $h->reset();
        $h->registerHandler(new SampleHandler1());
        $h->registerHandler(new SampleHandler2());
        $h->registerHandler(new SampleHandler3());
        $this->assertEquals(1, $h->getHandler('H1')->getPriority());
        $this->assertEquals(44, $h->getHandler('H2')->getPriority());
        $this->assertEquals(5, $h->getHandler('H3')->getPriority());
        $this->assertEquals([
           'Default', 'H1', 'H2', 'H3'
        ], array_map(function ($x) {
            return $x->getName();
        }, $h->getHandlers()));
        $h->sortHandlers();
        $this->assertEquals([
            'H2', 'H3', 'H1', 'Default'
        ], array_map(function ($x) {
            return $x->getName();
        }, $h->getHandlers()));
        $this->assertEquals([
            44, 5, 1, 0
        ], array_map(function ($x) {
            return $x->getPriority();
        }, $h->getHandlers()));
    }
    protected function tearDown(): void {
        $this->cleanupOutputBuffers();
        Handler::reset();
        restore_error_handler();
    }
    
    public function testHandel00() {
        Handler::reset();
        $output = $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler();
        });
        $this->assertStringContainsString('Application Error', $output); // CLI format uses title case
        // The output format may vary based on security settings
        $this->assertTrue(
            str_contains($output, 'Unknown line (Unknown Line)') || 
            str_contains($output, 'Application code'),
            'Expected output to contain location information'
        );
    }
    
    public function testHandel01() {
        Handler::reset();
        $output = $this->captureOutput(function() {
            Handler::get()->invokeExceptionsHandler(new \Exception("Test Exc", 33));
        });
        $this->assertStringContainsString('Application Error', $output); // CLI format uses title case
        // Assert a trace frame pointing at this test class is present, without
        // pinning the exact source line (which shifts with test-file edits and
        // call-stack ordering across PHP versions).
        $this->assertMatchesRegularExpression('/HandlerTest line \d+/', $output);
        $this->assertStringContainsString('Test Exc', $output);
    }
}
