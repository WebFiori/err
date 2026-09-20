<?php

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

$testsDirName = 'tests';
$rootDir = substr(__DIR__, 0, strlen(__DIR__) - strlen($testsDirName));
$DS = DIRECTORY_SEPARATOR;
$rootDirTrimmed = trim($rootDir,'/\\');
echo 'Include Path: \''.get_include_path().'\''."\n";

if (explode($DS, $rootDirTrimmed)[0] == 'home') {
    //linux.
    $rootDir = $DS.$rootDirTrimmed.$DS;
} else {
    $rootDir = $rootDirTrimmed.$DS;
}
define('ROOT_DIR', $rootDir);
const DS = DIRECTORY_SEPARATOR;
echo 'Root Directory: \''.$rootDir.'\'.'."\n";
$classesPath = $rootDir.'vendor/autoload.php';

if (file_exists($classesPath)) {
    require_once $classesPath;
} else {
    echo "Autoloader not found at: $classesPath\n";
    exit(1);
}

// Suppress error_log output during tests by redirecting to /dev/null
$originalErrorLog = ini_get('error_log');
ini_set('error_log', '/dev/null');

// Register a shutdown function to clean up
register_shutdown_function(function() use ($originalErrorLog) {
    // Restore original error log setting
    ini_set('error_log', $originalErrorLog);
});
