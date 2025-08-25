<?php

namespace WebFiori\Tests\Error;

use PHPUnit\Framework\TestCase;
use WebFiori\Error\TraceEntry;

/**
 * Tests for TraceEntry edge cases and various input scenarios.
 * 
 * @author Ibrahim
 */
class TraceEntryEdgeCasesTest extends TestCase {
    
    /**
     * Test TraceEntry with empty array.
     * 
     * @test
     */
    public function testEmptyTraceEntry(): void {
        $entry = new TraceEntry([]);
        $this->assertEquals('At class (Unknown Class)', (string)$entry);
        $this->assertEquals('(Unknown Class)', $entry->getClass());
        $this->assertEquals('(Unknown Line)', $entry->getLine());
        $this->assertEquals('', $entry->getMethod());
    }
    
    /**
     * Test TraceEntry with null values.
     * 
     * @test
     */
    public function testNullValues(): void {
        $entry = new TraceEntry([
            'class' => null,
            'file' => null,
            'line' => null,
            'function' => null
        ]);
        
        $this->assertEquals('At class (Unknown Class)', (string)$entry);
        $this->assertEquals('(Unknown Class)', $entry->getClass());
        $this->assertEquals('(Unknown Line)', $entry->getLine());
        $this->assertEquals('', $entry->getMethod());
    }
    
    /**
     * Test TraceEntry with various file path formats.
     * 
     * @test
     */
    public function testVariousFilePathFormats(): void {
        // Unix-style path
        $entry1 = new TraceEntry(['file' => '/path/to/MyClass.php']);
        $this->assertEquals('MyClass', $entry1->getClass());
        
        // Windows-style path
        $entry2 = new TraceEntry(['file' => 'C:\\path\\to\\AnotherClass.php']);
        $this->assertEquals('AnotherClass', $entry2->getClass());
        
        // Mixed separators
        $entry3 = new TraceEntry(['file' => '/path\\mixed/SeparatorClass.php']);
        $this->assertEquals('SeparatorClass', $entry3->getClass());
        
        // No extension
        $entry4 = new TraceEntry(['file' => '/path/to/NoExtension']);
        $this->assertEquals('NoExtension', $entry4->getClass());
        
        // Empty file name
        $entry5 = new TraceEntry(['file' => '/path/to/']);
        $this->assertEquals('(Unknown Class)', $entry5->getClass());
    }
    
    /**
     * Test class name extraction edge cases.
     * 
     * @test
     */
    public function testClassNameExtractionEdgeCases(): void {
        // Empty string
        $this->assertEquals('(Unknown Class)', TraceEntry::extractClassName(''));
        
        // Just a file name
        $this->assertEquals('SimpleFile', TraceEntry::extractClassName('SimpleFile.php'));
        
        // File with multiple dots
        $this->assertEquals('File', TraceEntry::extractClassName('File.test.php'));
        
        // File starting with dot
        $this->assertEquals('Hidden', TraceEntry::extractClassName('.hidden/Hidden.php'));
        
        // Very long path
        $longPath = str_repeat('/very/long/path', 10) . '/FinalClass.php';
        $this->assertEquals('FinalClass', TraceEntry::extractClassName($longPath));
    }
    
    /**
     * Test TraceEntry with complete information.
     * 
     * @test
     */
    public function testCompleteTraceEntry(): void {
        $entry = new TraceEntry([
            'class' => 'MyTestClass',
            'file' => '/path/to/MyTestClass.php',
            'line' => 42,
            'function' => 'testMethod'
        ]);
        
        $this->assertEquals('At class MyTestClass line 42', (string)$entry);
        $this->assertEquals('MyTestClass', $entry->getClass());
        $this->assertEquals('/path/to/MyTestClass.php', $entry->getFile());
        $this->assertEquals('42', $entry->getLine());
        $this->assertEquals('testMethod', $entry->getMethod());
    }
    
    /**
     * Test TraceEntry with numeric values as strings.
     * 
     * @test
     */
    public function testNumericStringValues(): void {
        $entry = new TraceEntry([
            'line' => '123',
            'class' => '456'  // Unusual but possible
        ]);
        
        $this->assertEquals('At class 456 line 123', (string)$entry);
        $this->assertEquals('456', $entry->getClass());
        $this->assertEquals('123', $entry->getLine());
    }
    
    /**
     * Test TraceEntry with special characters.
     * 
     * @test
     */
    public function testSpecialCharacters(): void {
        $entry = new TraceEntry([
            'class' => 'Class_With-Special$Characters',
            'file' => '/path/with spaces/Special-File.php',
            'function' => 'method_with_underscores'
        ]);
        
        $this->assertEquals('Class_With-Special$Characters', $entry->getClass());
        $this->assertEquals('/path/with spaces/Special-File.php', $entry->getFile());
        $this->assertEquals('method_with_underscores', $entry->getMethod());
    }
    
    /**
     * Test TraceEntry string conversion without line number.
     * 
     * @test
     */
    public function testStringConversionWithoutLine(): void {
        $entry = new TraceEntry([
            'class' => 'TestClass'
            // No line number provided
        ]);
        
        // Should not include line number when it's unknown
        $this->assertEquals('At class TestClass', (string)$entry);
    }
    
    /**
     * Test TraceEntry with boolean and array values (edge case).
     * 
     * @test
     */
    public function testUnexpectedValueTypes(): void {
        $entry = new TraceEntry([
            'class' => true,
            'line' => false,
            'function' => ['array', 'value']
        ]);
        
        // Should handle type conversion gracefully
        $this->assertEquals('1', $entry->getClass());
        $this->assertEquals('', $entry->getLine());
        $this->assertEquals('Array', $entry->getMethod());
    }
}
