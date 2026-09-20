# Log Callback Integration

Route error-handler logs through a callback instead of PHP's native
`error_log()` — with **no PSR-3 or external logging dependency**. This mirrors
the logging-via-callback standard used across the WebFiori ecosystem
(see [ADR-0031](https://github.com/WebFiori/docs/blob/main/adr/0031-ai-logging-via-callback.md)).

## Run

```bash
php examples/integrations/02-log-callback/example.php
```

## Callback signature

```php
fn(string $level, string $message, array $context): void
```

Levels: `debug`, `info`, `warning`, `error`.

## Two injection points

```php
use WebFiori\Error\Handler;

// Global — used by every handler and the library's internal diagnostics
Handler::setDefaultLogCallback(function (string $level, string $message, array $context): void {
    // send $message/$context to your sink of choice
});

// Per-handler — overrides the global callback for one handler
$handler->setLogCallback(function (string $level, string $message, array $context): void {
    // ...
});
```

When no callback is configured, logging falls back to `error_log()`, so existing
behavior is unchanged.

## Bridging to PSR-3 (Monolog, etc.)

The library does not depend on PSR-3, but a PSR-3 logger bridges in one line
because `LoggerInterface::log($level, $message, $context)` matches the callback
signature:

```php
Handler::setDefaultLogCallback([$psrLogger, 'log']);
```
