<?php

/**
 * Log Callback Example
 *
 * Demonstrates routing error-handler logs through a callback instead of PHP's
 * native error_log() — without any PSR-3 / external logging dependency.
 *
 * Two injection points:
 *   1. Handler::setDefaultLogCallback()  — global, used by all handlers and by
 *      the library's internal diagnostics.
 *   2. AbstractHandler::setLogCallback() — per-handler override.
 *
 * The callback signature matches the WebFiori logging standard (ADR-0031):
 *   fn(string $level, string $message, array $context): void
 * with levels: debug, info, warning, error.
 */

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Error\AbstractHandler;
use WebFiori\Error\Config\HandlerConfig;
use WebFiori\Error\Handler;

Handler::setConfig(HandlerConfig::createDevelopmentConfig());

// ─── 1. Global callback — receives logs from every handler ────────────────────

Handler::setDefaultLogCallback(function (string $level, string $message, array $context): void
{
    $line = sprintf('[%s] %s', strtoupper($level), $message);

    if (!empty($context)) {
        $line .= ' '.json_encode($context);
    }
    echo $line."\n";
});

// A handler that logs via secureLog() (sanitized + routed through the callback).
class OrderHandler extends AbstractHandler {
    public function __construct() {
        parent::__construct();
        $this->setName('OrderHandler');
    }

    public function handle(): void {
        $this->secureLog('Order processing failed', [
            'exception' => $this->getClass(),
            'message' => $this->getMessage(),
        ]);
    }

    public function isActive(): bool {
        return true;
    }

    public function isShutdownHandler(): bool {
        return false;
    }
}

Handler::registerHandler(new OrderHandler());

// ─── 2. Per-handler callback — overrides the global one for this handler ──────

$auditHandler = new OrderHandler();
$auditHandler->setName('AuditHandler');
$auditHandler->setLogCallback(function (string $level, string $message, array $context): void
{
    // e.g. write to an audit sink; here we just tag it.
    echo "AUDIT <{$level}> {$message}\n";
});
Handler::registerHandler($auditHandler);

// ─── 3. Bridging to a PSR-3 logger (Monolog, etc.) — one line ─────────────────
//
// No PSR-3 dependency is required by the library; if your app already has a
// PSR-3 logger, bridge it directly:
//
//   Handler::setDefaultLogCallback([$psrLogger, 'log']);
//
// ($psrLogger->log($level, $message, $context) matches the callback signature.)

// ─── Trigger an exception to see the handlers log through the callbacks ───────

Handler::get()->invokeExceptionsHandler(new Exception('Payment gateway timeout', 504));

echo "\nDone. Logs above were emitted via callbacks, not error_log().\n";
