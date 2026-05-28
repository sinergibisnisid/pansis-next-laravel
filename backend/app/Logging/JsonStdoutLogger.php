<?php

namespace App\Logging;

use Illuminate\Support\Str;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Build a Monolog channel that writes structured JSON lines to stdout.
 *
 * The output is one JSON object per log line, suitable for Promtail → Loki,
 * Filebeat → Elasticsearch, or `docker logs --tail=...`.
 *
 * Each record carries:
 *   timestamp, level, level_name, channel, message, context (incl. correlation_id),
 *   service, environment, hostname, version.
 */
class JsonStdoutLogger
{
    public function __invoke(array $config): Logger
    {
        $level = $config['level'] ?? 'info';
        $channel = $config['name'] ?? 'pansin';

        $logger = new Logger($channel);

        $handler = new StreamHandler('php://stdout', $level);
        $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, true));
        $handler->pushProcessor(new ContextProcessor());

        $logger->pushHandler($handler);

        return $logger;
    }
}

/**
 * Decorates every record with service-level metadata + correlation_id.
 */
class ContextProcessor
{
    public function __invoke(\Monolog\LogRecord $record): \Monolog\LogRecord
    {
        $extra = $record->extra;
        $extra['service'] = config('app.name', 'pansin-access');
        $extra['environment'] = config('app.env', 'production');
        $extra['version'] = config('app.version', '1.0.0');
        $extra['hostname'] = gethostname() ?: 'unknown';

        // Pull correlation_id from the singleton if available.
        $cid = app()->bound('correlation_id') ? app('correlation_id') : null;
        if (!$cid) {
            // Fallback: per-process random id so log lines from a single process can be grouped.
            $cid = Str::random(16);
        }
        $extra['correlation_id'] = $cid;

        return $record->with(extra: $extra);
    }
}
