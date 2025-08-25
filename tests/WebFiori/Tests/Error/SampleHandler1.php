<?php

namespace WebFiori\Tests\Error;

use WebFiori\Error\AbstractHandler;

class SampleHandler1 extends AbstractHandler {
    public function __construct() {
        parent::__construct();
        $this->setPriority(1);
        $this->setName('H1');
    }
    
    public function handle(): void {
        !defined('SampleHandler1') ? define('SampleHandler1', 'Yes') : '';
    }

    public function isActive(): bool {
        return true;
    }

    public function isShutdownHandler(): bool {
        return false;
    }
}
