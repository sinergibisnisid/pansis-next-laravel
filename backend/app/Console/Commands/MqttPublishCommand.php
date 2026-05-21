<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;

class MqttPublishCommand extends Command
{
    protected $signature = 'mqtt:publish {topic} {payload}';

    protected $description = 'Publish a message to MQTT topic';

    public function handle(): int
    {
        $topic = $this->argument('topic');
        $payload = $this->argument('payload');

        try {
            $client = MQTT::connection();
            $client->publish($topic, $payload, config('mqtt.qos.default', 1));
            $client->disconnect();

            $this->info("Message published successfully to topic: {$topic}");
            Log::info('MQTT message published manually', [
                'topic' => $topic,
                'payload' => $payload,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to publish message: {$e->getMessage()}");
            Log::error('Failed to publish MQTT message', [
                'topic' => $topic,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
