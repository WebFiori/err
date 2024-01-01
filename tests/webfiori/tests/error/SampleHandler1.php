<?php


namespace webfiori\tests\error;

use webfiori\error\AbstractHandler;

class SampleHandler1 extends AbstractHandler {
    public function __construct() {
        parent::__construct();
    }
    public function handle() {
        define('SampleHandler1', 'Yes');
    }

    public function isActive(): bool {
        return true;
    }

    public function isShutdownHandler(): bool {
        return false;
    }
}
