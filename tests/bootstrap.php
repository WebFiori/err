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
$classesPath = $rootDir.'webfiori'.DS.'error'.DS;

require_once $classesPath . 'ErrorHandlerException.php';
require_once $classesPath . 'AbstractHandler.php';
require_once $classesPath . 'DefaultHandler.php';
require_once $classesPath . 'TraceEntry.php';
require_once $classesPath . 'Handler.php';


