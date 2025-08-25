<?php
namespace WebFiori\Error;

/**
 * Represents an entry in an exception stack trace or debug backtrace.
 * 
 * This class encapsulates information about a single step in the execution
 * stack, including:
 * - The class where the call occurred
 * - The file path
 * - The line number
 * - The method/function that was called
 * 
 * Usage Example:
 * ```php
 * $traceEntry = new TraceEntry([
 *     'class' => 'MyClass',
 *     'file' => '/path/to/file.php',
 *     'line' => 42,
 *     'function' => 'myMethod'
 * ]);
 * 
 * echo $traceEntry; // "At class MyClass line 42"
 * ```
 *
 * @author Ibrahim
 */
class TraceEntry {
    /**
     * @var string The class name where the call occurred
     */
    private string $class;
    
    /**
     * @var string The file path where the call occurred
     */
    private string $file;
    
    /**
     * @var string The line number where the call occurred
     */
    private string $line;
    
    /**
     * @var string The method or function name that was called
     */
    private string $method;
    
    /**
     * Creates new instance of the class.
     * 
     * @param array<string, mixed> $debugTraceEntry An associative array that holds trace entry information.
     * The array can have following indices:
     * - 'class': The class name (optional)
     * - 'file': The file path (optional)
     * - 'line': The line number (optional)
     * - 'function': The function/method name (optional)
     * 
     * @throws InvalidArgumentException If the trace entry array is malformed
     */
    public function __construct(array $debugTraceEntry) {
        $this->initializeFromTraceEntry($debugTraceEntry);
    }
    
    /**
     * Initialize properties from the debug trace entry.
     * 
     * @param array<string, mixed> $debugTraceEntry The trace entry data
     */
    private function initializeFromTraceEntry(array $debugTraceEntry): void {
        $this->method = $this->extractMethod($debugTraceEntry);
        $this->file = $this->extractFile($debugTraceEntry);
        $this->line = $this->extractLine($debugTraceEntry);
        $this->class = $this->extractClass($debugTraceEntry);
    }
    
    /**
     * Extract method name from trace entry.
     * 
     * @param array<string, mixed> $debugTraceEntry The trace entry data
     * @return string The method name or empty string
     */
    private function extractMethod(array $debugTraceEntry): string {
        $function = $debugTraceEntry['function'] ?? '';
        return is_array($function) ? 'Array' : (string)$function;
    }
    
    /**
     * Extract file path from trace entry.
     * 
     * @param array<string, mixed> $debugTraceEntry The trace entry data
     * @return string The file path or method name as fallback
     */
    private function extractFile(array $debugTraceEntry): string {
        return (string)($debugTraceEntry['file'] ?? $this->method);
    }
    
    /**
     * Extract line number from trace entry.
     * 
     * @param array<string, mixed> $debugTraceEntry The trace entry data
     * @return string The line number or "(Unknown Line)"
     */
    private function extractLine(array $debugTraceEntry): string {
        $line = $debugTraceEntry['line'] ?? '(Unknown Line)';
        if (is_array($line)) {
            return 'Array';
        }
        if (is_bool($line)) {
            return $line ? '1' : '';
        }
        return (string)$line;
    }
    
    /**
     * Extract class name from trace entry.
     * 
     * @param array<string, mixed> $debugTraceEntry The trace entry data
     * @return string The class name or extracted from file path
     */
    private function extractClass(array $debugTraceEntry): string {
        $class = $debugTraceEntry['class'] ?? null;
        if ($class !== null) {
            return is_array($class) ? 'Array' : (string)$class;
        }
        return self::extractClassName($this->file);
    }
    
    /**
     * Converts the entry to a human-readable string representation.
     * 
     * @return string A formatted string describing the trace entry location
     */
    public function __toString(): string {
        return $this->formatTraceEntry();
    }
    
    /**
     * Format the trace entry as a readable string.
     * 
     * @return string The formatted trace entry
     */
    private function formatTraceEntry(): string {
        $baseString = sprintf('At class %s', $this->getClass());
        
        if ($this->hasValidLine()) {
            $baseString .= sprintf(' line %s', $this->getLine());
        }
        
        return $baseString;
    }
    
    /**
     * Check if the line number is valid (not the unknown placeholder).
     * 
     * @return bool True if line number is valid
     */
    private function hasValidLine(): bool {
        return $this->getLine() !== '(Unknown Line)';
    }
    
    /**
     * Extract PHP class name from a file path.
     * 
     * This method attempts to determine the class name based on the file name,
     * following common PHP naming conventions where the class name matches
     * the file name.
     * 
     * @param string $filePath The path to the file that represents the class
     * 
     * @return string A string that represents class name. If no name can be extracted,
     * returns '(Unknown Class)'.
     */
    public static function extractClassName(string $filePath): string {
        if (empty($filePath)) {
            return '(Unknown Class)';
        }
        
        $normalizedPath = self::normalizePath($filePath);
        $pathSegments = explode(DIRECTORY_SEPARATOR, $normalizedPath);
        
        // Get the last segment (file name)
        $fileName = end($pathSegments);
        
        if (empty($fileName)) {
            return '(Unknown Class)';
        }
        
        return self::extractClassNameFromFile($fileName);
    }
    
    /**
     * Normalize file path separators.
     * 
     * @param string $filePath The file path to normalize
     * @return string The normalized path
     */
    private static function normalizePath(string $filePath): string {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePath);
    }
    
    /**
     * Extract class name from a file name.
     * 
     * @param string $fileName The file name (e.g., "MyClass.php")
     * @return string The extracted class name
     */
    private static function extractClassNameFromFile(string $fileName): string {
        if (empty($fileName)) {
            return '(Unknown Class)';
        }
        
        // Remove file extension
        $baseName = explode('.', $fileName)[0];
        
        if (empty($baseName)) {
            return '(Unknown Class)';
        }
        
        // Capitalize first letter and return
        return ucfirst($baseName);
    }
    
    /**
     * Returns the name of the class that the entry represents.
     * 
     * @return string The name of the class that the entry represents
     */
    public function getClass(): string {
        return $this->class;
    }
    
    /**
     * Returns the file path of the entry.
     * 
     * @return string The file path of the entry
     */
    public function getFile(): string {
        return $this->file;
    }
    
    /**
     * Returns the line number of the entry.
     * 
     * @return string The line number of the entry
     */
    public function getLine(): string {
        return $this->line;
    }
    
    /**
     * Returns the method or function name of the entry.
     * 
     * @return string The method or function name
     */
    public function getMethod(): string {
        return $this->method;
    }
}
