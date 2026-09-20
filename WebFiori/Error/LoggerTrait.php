<?php
namespace WebFiori\Error;

/**
 * Provides logging capability via a user-supplied callback function.
 *
 * Classes that use this trait can emit log messages at standard levels
 * (debug, info, warning, error) without depending on any logging library.
 * If no callback is configured (neither on the instance nor globally via
 * {@see Handler::setDefaultLogCallback()}), logging falls back to PHP's
 * native error_log(), preserving the library's original behavior.
 *
 * This mirrors the logging-via-callback standard adopted across the WebFiori
 * ecosystem (see ADR-0031), so a developer who knows one library's logging
 * model already knows this one. PSR-3 users bridge in a single line:
 *
 * ```php
 * $handler->setLogCallback([$psrLogger, 'log']);
 * ```
 *
 * @author Ibrahim
 */
trait LoggerTrait {
    /**
     * The logging callback function.
     *
     * Signature: fn(string $level, string $message, array $context): void
     *
     * @var callable|null
     */
    private $logCallback = null;

    /**
     * Returns the currently configured log callback for this instance.
     *
     * @return callable|null The log callback, or null if not configured.
     */
    public function getLogCallback(): ?callable {
        return $this->logCallback;
    }

    /**
     * Sets a callback function for logging on this instance.
     *
     * The callback receives three arguments: the level ('debug', 'info',
     * 'warning', 'error'), the message, and a structured context array.
     *
     * @param callable|null $callback The logging callback, or null to disable
     *        instance-level logging (falling back to the global callback or
     *        error_log()).
     */
    public function setLogCallback(?callable $callback): void {
        $this->logCallback = $callback;
    }

    /**
     * Emits a log message at the specified level.
     *
     * Resolution order: instance callback → global default callback
     * ({@see Handler::getDefaultLogCallback()}) → native error_log() fallback.
     *
     * @param string $level The log level ('debug', 'info', 'warning', 'error').
     * @param string $message The log message.
     * @param array<string, mixed> $context Structured context data.
     */
    private function log(string $level, string $message, array $context): void {
        $callback = $this->logCallback ?? Handler::getDefaultLogCallback();

        if ($callback !== null) {
            $callback($level, $message, $context);

            return;
        }

        // Fallback: preserve original behavior via PHP's error log.
        if (!empty($context)) {
            error_log('['.$level.'] '.$message.' '.json_encode($context));
        } else {
            error_log('['.$level.'] '.$message);
        }
    }

    /**
     * Emits a debug-level log message.
     *
     * @param string $message The log message.
     * @param array<string, mixed> $context Structured context data.
     */
    protected function logDebug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }

    /**
     * Emits an error-level log message.
     *
     * @param string $message The log message.
     * @param array<string, mixed> $context Structured context data.
     */
    protected function logError(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }

    /**
     * Emits an info-level log message.
     *
     * @param string $message The log message.
     * @param array<string, mixed> $context Structured context data.
     */
    protected function logInfo(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }

    /**
     * Emits a warning-level log message.
     *
     * @param string $message The log message.
     * @param array<string, mixed> $context Structured context data.
     */
    protected function logWarning(string $message, array $context = []): void {
        $this->log('warning', $message, $context);
    }
}
