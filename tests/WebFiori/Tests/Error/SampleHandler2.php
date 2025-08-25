<?php


namespace WebFiori\Tests\Error;

use WebFiori\Error\AbstractHandler;

class SampleHandler2 extends AbstractHandler {
    public function __construct() {
        parent::__construct();
        $this->setPriority(44);
        $this->setName('H2');
    }
    public function handle() {
       !defined('SampleHandler2') ? define('SampleHandler2', 'Yes') : '';
    }

    public function isActive(): bool {
        return true;
    }

    public function isShutdownHandler(): bool {
        return true;
    }
}
