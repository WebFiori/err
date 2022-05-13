<?php

namespace webfiori\tests\error;

namespace webfiori\tests\error;

use PHPUnit\Framework\TestCase;
use webfiori\error\TraceEntry;
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
        $h = Handler::get();
        $this->assertTrue(true);
    }
}
