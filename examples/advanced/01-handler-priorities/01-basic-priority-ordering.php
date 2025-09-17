<?php
/**
 * Section 1: Basic Priority Ordering
 */

require_once __DIR__ . '/00-shared-classes.php';

use WebFiori\Error\Handler;
use WebFiori\Error\Config\HandlerConfig;

Handler::setConfig(HandlerConfig::createDevelopmentConfig());

class HighPriorityHandler extends PriorityDemoHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('HighPriority');
        $this->setPriority(100);
    }
}

class MediumPriorityHandler extends PriorityDemoHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('MediumPriority');
        $this->setPriority(50);
    }
}

class LowPriorityHandler extends PriorityDemoHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('LowPriority');
        $this->setPriority(10);
    }
}

class CriticalHandler extends PriorityDemoHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('Critical');
        $this->setPriority(1000);
    }
}

class DefaultPriorityHandler extends PriorityDemoHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('Default');
    }
}

echo "Section 1: Basic Priority Ordering\n";
echo str_repeat('-', 35) . "\n";

PriorityDemoHandler::resetCounter();

$handlers = [
    new LowPriorityHandler(),
    new CriticalHandler(),
    new MediumPriorityHandler(),
    new DefaultPriorityHandler(),
    new HighPriorityHandler()
];

foreach ($handlers as $handler) {
    Handler::registerHandler($handler);
}

echo "Registered handlers in random order:\n";
foreach ($handlers as $handler) {
    echo "- {$handler->getName()} (Priority: {$handler->getPriority()})\n";
}

echo "\nTriggering exception to see execution order:\n";

try {
    throw new Exception('Priority test exception');
} catch (Exception $e) {
    Handler::handleException($e);
}

echo "\nActual execution order shown above.\nExpected order: Critical(1000) → High(100) → Medium(50) → Low(10) → Default(0)\n";
