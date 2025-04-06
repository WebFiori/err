<?php
namespace webfiori\tests\error;

use PHPUnit\Framework\TestCase;
use webfiori\error\TraceEntry;
/**
 * Description of TraceEntryTest
 *
 * @author Ibrahim
 */
class TraceEntryTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $entry = new TraceEntry([]);
        $this->assertEquals('At class (Unkwon Class) line (Unkown Line)', $entry.'');
        $this->assertTrue(true);
    }
    /**
     * @test
     */
    public function test01() {
        $entry = new TraceEntry([
            'class' => 'Router',
            'line' => 30
        ]);
        $this->assertEquals('At class Router line 30', $entry.'');
        $this->assertTrue(true);
    }
    /**
     * @test
     */
    public function test02() {
        $entry = new TraceEntry([
            'file' => 'super\\x\\y\NomeRoom.php',
            'line' => 30
        ]);
        $this->assertEquals('At class NomeRoom line 30', $entry.'');
        $this->assertTrue(true);
    }
}
