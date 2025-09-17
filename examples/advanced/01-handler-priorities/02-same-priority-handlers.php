<?php
/**
 * Section 2: Handlers with Same Priority
 */

require_once __DIR__ . '/00-shared-classes.php';

use WebFiori\Error\Handler;
use WebFiori\Error\Config\HandlerConfig;

Handler::setConfig(HandlerConfig::createDevelopmentConfig());

class SamePriorityA extends PriorityDemoHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('SamePriorityA');
        $this->setPriority(50);
    }
}

class SamePriorityB extends PriorityDemoHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('SamePriorityB');
        $this->setPriority(50);
    }
}

class SamePriorityC extends PriorityDemoHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('SamePriorityC');
        $this->setPriority(50);
    }
}

echo "Section 2: Handlers with Same Priority\n";
echo str_repeat('-', 35) . "\n";

PriorityDemoHandler::resetCounter();

$sameHandlers = [
    new SamePriorityA(),
    new SamePriorityB(),
    new SamePriorityC()
];

foreach ($sameHandlers as $handler) {
    Handler::registerHandler($handler);
}

echo "Registered 3 handlers with same priority (50):\n";
foreach ($sameHandlers as $handler) {
    echo "- {$handler->getName()}\n";
}

echo "\nTriggering exception:\n";

try {
    throw new Exception('Same priority test exception');
} catch (Exception $e) {
    Handler::handleException($e);
}

echo "\nNote: Handlers with same priority execute in registration order.\n";
