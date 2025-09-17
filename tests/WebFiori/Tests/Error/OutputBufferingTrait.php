<?php

namespace WebFiori\Tests\Error;

/**
 * Trait for managing output buffering in tests to ensure clean PHPUnit output.
 * 
 * This trait provides methods to capture and manage output from error handlers
 * during testing, preventing verbose HTML and JSON output from cluttering
 * the test results.
 * 
 * ## Problem Solved
 * 
 * Without output buffering, error handler tests produce verbose output like:
 * - HTML error displays with inline CSS
 * - JSON log entries mixed with test output
 * - Cluttered console output making it hard to read test results
 * 
 * ## Solution
 * 
 * This trait provides:
 * - Clean output capture using PHP's output buffering
 * - Assertion helpers for testing captured content
 * - Automatic cleanup to prevent buffer leaks
 * - Consistent test output formatting
 * 
 * ## Usage Example
 * 
 * ```php
 * class MyTest extends TestCase {
 *     use OutputBufferingTrait;
 *     
 *     protected function tearDown(): void {
 *         $this->cleanupOutputBuffers();
 *     }
 *     
 *     public function testErrorHandler(): void {
 *         $output = $this->captureOutput(function() {
 *             Handler::get()->invokeExceptionsHandler(new Exception('Test'));
 *         });
 *         
 *         $this->assertOutputContains('Application Error');
 *     }
 * }
 * ```
 * 
 * ## Benefits
 * 
 * - ✓ Clean PHPUnit test output
 * - ✓ Captured handler output for assertions
 * - ✓ No verbose HTML in console
 * - ✓ Proper buffer management
 * - ✓ Consistent test experience
 * 
 * @author Ibrahim
 */
trait OutputBufferingTrait {
    
    /**
     * Captured output from the last buffered operation.
     */
    private string $capturedOutput = '';
    
    /**
     * Original output buffer level before our operations.
     */
    private int $originalBufferLevel = 0;
    
    /**
     * Start output buffering to capture handler output.
     */
    protected function startOutputCapture(): void {
        $this->originalBufferLevel = ob_get_level();
        ob_start();
    }
    
    /**
     * Stop output buffering and capture the output.
     * 
     * @return string The captured output
     */
    protected function stopOutputCapture(): string {
        $currentLevel = ob_get_level();
        
        if ($currentLevel > $this->originalBufferLevel) {
            $this->capturedOutput = ob_get_contents() ?: '';
            ob_end_clean();
        } else {
            $this->capturedOutput = '';
        }
        
        return $this->capturedOutput;
    }
    
    /**
     * Get the last captured output.
     * 
     * @return string The captured output
     */
    protected function getCapturedOutput(): string {
        return $this->capturedOutput;
    }
    
    /**
     * Execute a callable while capturing its output.
     * 
     * @param callable $callback The function to execute
     * @return string The captured output
     */
    protected function captureOutput(callable $callback): string {
        // Store initial buffer level
        $initialLevel = ob_get_level();
        
        // Start a new buffer level specifically for this capture
        ob_start();
        
        try {
            $callback();
            $output = ob_get_contents() ?: '';
            
            // Only clean buffers we created
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
            
            $this->capturedOutput = $output;
            return $output;
        } catch (\Throwable $e) {
            // Clean up only buffers we created
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
            throw $e;
        }
    }
    
    /**
     * Assert that the captured output contains specific content.
     * 
     * @param string $expectedContent The content to search for
     * @param string $message Optional assertion message
     */
    protected function assertOutputContains(string $expectedContent, string $message = ''): void {
        $this->assertStringContainsString(
            $expectedContent, 
            $this->capturedOutput, 
            $message ?: "Expected output to contain: {$expectedContent}"
        );
    }
    
    /**
     * Assert that the captured output does not contain specific content.
     * 
     * @param string $unexpectedContent The content that should not be present
     * @param string $message Optional assertion message
     */
    protected function assertOutputNotContains(string $unexpectedContent, string $message = ''): void {
        $this->assertStringNotContainsString(
            $unexpectedContent, 
            $this->capturedOutput, 
            $message ?: "Expected output to not contain: {$unexpectedContent}"
        );
    }
    
    /**
     * Assert that no output was captured.
     * 
     * @param string $message Optional assertion message
     */
    protected function assertNoOutput(string $message = ''): void {
        $this->assertEmpty(
            $this->capturedOutput, 
            $message ?: 'Expected no output to be generated'
        );
    }
    
    /**
     * Clean up any remaining output buffers.
     * This should be called in tearDown() methods.
     */
    protected function cleanupOutputBuffers(): void {
        // Reset our captured output
        $this->capturedOutput = '';
        $this->originalBufferLevel = 0;
        
        // Don't aggressively clean buffers to avoid PHPUnit conflicts
        // Let PHPUnit manage its own buffers
    }
}
