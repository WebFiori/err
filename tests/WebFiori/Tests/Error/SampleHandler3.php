<?php


namespace WebFiori\Tests\Error;

use WebFiori\Error\AbstractHandler;

class SampleHandler3 extends AbstractHandler {
    public function __construct() {
        parent::__construct();
        $this->setPriority(5);
        $this->setName('H3');
    }
    public function handle(): void {
        !defined('SampleHandler3') ? define('SampleHandler3', 'Yes') : '';
    }

    public function isActive(): bool {
        return true;
    }

    public function isShutdownHandler(): bool {
        return true;
    }
}
