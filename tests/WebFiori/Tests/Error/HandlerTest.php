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
    /**
     * @test
     */
    public function test00() {
        $this->expectException(ErrorHandlerException::class);
        if (PHP_MAJOR_VERSION == 7) {
            $msg = 'Run-time notice: Undefined variable: y at HandlerTest Line 32';
        } else {
            $msg = 'An exception caused by an error. Run-time warning: Undefined variable $y at HandlerTest Line 32';
        }
        $this->expectExceptionMessage($msg);
        $h = Handler::get();
        $x = $y;
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
        $h->invokExceptionsHandler();
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
        $h->invokExceptionsHandler();
        $this->assertFalse(defined('SampleHandler2'));
        $h->unregisterHandler($h->getHandler('Default'));
        $h->invokShutdownHandler();
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
    public function testHandel00() {
        ob_start();
        Handler::reset();
        Handler::get()->invokExceptionsHandler();
        $output = ob_get_contents();
        ob_end_flush();
        $this->assertEquals("<pre>\n"
                . "An exception was thrown at (Unkwon Class) line (Unkwon Line).\n"
                . "Exception message: No Message.\n"
                . "Stack trace:\n"
                . "(No Trace)\n"
                . "</pre>", $output);
    }
    public function testHandel01() {
        ob_start();
        Handler::reset();
        Handler::get()->invokExceptionsHandler(new \Exception("Test Exc", 33));
        $output = ob_get_contents();
        ob_end_flush();
        $this->assertEquals("<pre>\n"
                . "An exception was thrown at HandlerTest line 137.\n"
                . "Exception message: Test Exc.\n"
                . "Stack trace:\n", substr($output, 0, 97));
    }
}
