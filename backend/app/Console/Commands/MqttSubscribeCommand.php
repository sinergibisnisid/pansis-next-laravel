<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMqttMessageJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;
use PhpMqtt\Client\MqttClient;

class MqttSubscribeCommand extends Command
{
    protected $signature = 'mqtt:subscribe';

    protected $description = 'Subscribe to MQTT topics and process incoming messages';

    protected bool $shouldRun = true;

    protected int $reconnectAttempts = 0;

    public function handle(): int
    {
        $this->info('Starting MQTT subscriber...');

        $this->registerSignalHandlers();

        while ($this->shouldRun) {
            try {
                $this->subscribe();
            } catch (\Throwable $e) {
                $this->handleDisconnection($e);
            }
        }

        $this->info('MQTT subscriber stopped gracefully.');

        return self::SUCCESS;
    }

    protected function subscribe(): void
    {
        $client = MQTT::connection();

        $this->reconnectAttempts = 0;
        $this->info('Connected to MQTT broker successfully.');
        Log::info('MQTT subscriber connected to broker');

        $topics = config('mqtt.topics');
        $qosConfig = config('mqtt.qos');

        foreach ($topics as $key => $topic) {
            $qos = match (true) {
                str_contains($key, 'alarm') || str_contains($key, 'emergency') => $qosConfig['alarm'] ?? 2,
                str_contains($key, 'heartbeat') => $qosConfig['heartbeat'] ?? 0,
                default => $qosConfig['default'] ?? 1,
            };

            $client->subscribe($topic, function (string $topic, string $message) {
                $this->processMessage($topic, $message);
            }, $qos);

            $this->info("Subscribed to: {$topic} (QoS: {$qos})");
        }

        while ($this->shouldRun) {
            $client->loop(true);
            usleep(100000); // 100ms
        }

        $client->disconnect();
    }

    protected function processMessage(string $topic, string $message): void
    {
        $receivedAt = now()->toIso8601String();

        $this->line("[{$receivedAt}] Received message on topic: {$topic}");

        ProcessMqttMessageJob::dispatch(
            topic: $topic,
            payload: $message,
            receivedAt: $receivedAt,
        );
    }

    protected function handleDisconnection(\Throwable $e): void
    {
        $maxAttempts = config('mqtt.reconnect.max_attempts', 10);
        $baseDelay = config('mqtt.reconnect.base_delay', 1);
        $maxDelay = config('mqtt.reconnect.max_delay', 60);

        $this->reconnectAttempts++;

        if ($this->reconnectAttempts > $maxAttempts) {
            $this->error("Max reconnection attempts ({$maxAttempts}) reached. Stopping.");
            Log::critical('MQTT subscriber exceeded max reconnection attempts', [
                'error' => $e->getMessage(),
                'attempts' => $this->reconnectAttempts,
            ]);
            $this->shouldRun = false;
            return;
        }

        $delay = min($baseDelay * (2 ** ($this->reconnectAttempts - 1)), $maxDelay);

        $this->warn("Connection lost. Reconnecting in {$delay}s (attempt {$this->reconnectAttempts}/{$maxAttempts})...");
        Log::warning('MQTT connection lost, attempting reconnect', [
            'error' => $e->getMessage(),
            'attempt' => $this->reconnectAttempts,
            'delay' => $delay,
        ]);

        sleep($delay);
    }

    protected function registerSignalHandlers(): void
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, function () {
                $this->info('Received SIGTERM, shutting down...');
                $this->shouldRun = false;
            });

            pcntl_signal(SIGINT, function () {
                $this->info('Received SIGINT, shutting down...');
                $this->shouldRun = false;
            });
        }
    }
}
