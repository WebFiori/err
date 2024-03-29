<?php

namespace webfiori\tests\error;

require_once 'SampleHandler1.php';
require_once 'SampleHandler2.php';

use PHPUnit\Framework\TestCase;
use webfiori\error\ErrorHandlerException;
use webfiori\error\Handler;
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
            $msg = 'Run-time notice: Undefined variable: y at HandlerTest Line 29';
        } else {
            $msg = 'An exception caused by an error. Run-time warning: Undefined variable $y at HandlerTest Line 29';
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
        $this->assertFalse($h->hasHandler('New Handler'));
        $h->registerHandler(new SampleHandler1());
        $this->assertTrue($h->hasHandler('New Handler'));
        $h->unregisterHandler($h->getHandler('New Handler'));
        $this->assertFalse($h->hasHandler('New Handler'));
    }
    /**
     * @test
     */
    public function test02() {
        $h = Handler::get();
        $h->reset();
        $h->registerHandler(new SampleHandler1());
        $this->assertFalse(defined('SampleHandler1'));
        $h->invokExceptionHandler();
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
        $h->invokExceptionHandler();
        $this->assertFalse(defined('SampleHandler2'));
        $h->unregisterHandler($h->getHandler('Default'));
        $h->invokShutdownHandler();
        $this->assertTrue(defined('SampleHandler2'));
    }
}
