<?php
namespace webfiori\error;

/**
 * A class which is used to represent an entry in exception stack trace or 
 * a trace which is returned by the function debug_backtrace(). 
 *
 * @author Ibrahim
 */
class TraceEntry {
    private $class;
    private $file;
    private $line;
    private $method;
    /**
     * Creates new instance of the class.
     * 
     * @param array $debugTraceEntry An associative array that holds trace entry information.
     * the array can have following indices:
     * <ul>
     * <li>class</li>
     * <li>file</li>
     * <li>line</li>
     * <li>function</li>
     * </ul>
     */
    public function __construct(array $debugTraceEntry) {
        $this->method = isset($debugTraceEntry['function']) ? $debugTraceEntry['function'] : '';
        $this->file = isset($debugTraceEntry['file']) ? $debugTraceEntry['file'] : $this->method;
        $this->line = isset($debugTraceEntry['line']) ? $debugTraceEntry['line'] : 'X';
        $this->class = isset($debugTraceEntry['class']) ? $debugTraceEntry['class'] : self::extractClassName($this->file);
    }
    /**
     * Converts the entry to a string.
     * 
     * @return string
     */
    public function __toString() {
        $line = $this->getLine();
        $class = $this->getClass();
        
        if ($class == 'X') {
            $retVal = 'NO CLASS';
        } else {
            $retVal = 'At class '.$this->getClass();
        }
        
        if ($line != 'X') {
            $retVal .= ' line '.$line;
        }
        
        return $retVal;
    }
    /**
     * Extract PHP's class name based on the file name of the class/
     * 
     * @param string $filePath The path to the file that represents the class.
     * 
     * @return string A string that represents class name. If no name is extracted,
     * the method will return  the string 'X'.
     */
    public static function extractClassName(string $filePath) : string {
        $fixed = str_replace('\\', DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR, $filePath));
        $expl = explode(DIRECTORY_SEPARATOR, $fixed);

        if (count($expl) <= 1) {
            return 'X';
        }
        $classFile = $expl[count($expl) - 1];
        $firstChar = $classFile[0];

        return strtoupper($firstChar).''.explode('.', substr($classFile, 1))[0];
    }
    /**
     * Returns the name of the class that the entry represents.
     * 
     * @return string The name of the class that the entry represents.
     */
    public function getClass() : string {
        return $this->class;
    }
    /**
     * Returns a string that represents the file of the entry.
     * 
     * @return string A string that represents the file of the entry.
     */
    public function getFile() : string {
        return $this->file;
    }
    /**
     * Returns a string that represents line number of the entry.
     * 
     * @return string A string that represents line number of the entry.
     */
    public function getLine() : string {
        return $this->line;
    }
    /**
     * Returns a string that represents the method of the entry.
     * 
     * @return string
     */
    public function getMethod() : string {
        return $this->method;
    }
}
