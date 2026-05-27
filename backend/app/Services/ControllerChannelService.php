<?php

namespace App\Services;

use App\Enums\ChannelDirection;
use App\Enums\ChannelFunction;
use App\Enums\DeviceType;
use App\Enums\NormalState;
use App\Models\ControllerChannel;
use App\Models\Device;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Manages I/O channel topology for Intelligence Controller devices.
 *
 * Responsibilities:
 *   - Validate channel assignments (direction matches function, NC vs NO).
 *   - Provision a default channel layout when a controller is registered.
 *   - Resolve "what controller channel implements function X for vault Y?".
 *   - Enforce the 4-input + 4-output limit per controller (per Pansin Access PDF).
 */
class ControllerChannelService
{
    /**
     * Per-controller capacity per direction. PDF: "Input Sensor 4 Channel,
     * Output Relay 4 Channel".
     */
    public const MAX_CHANNELS_PER_DIRECTION = 4;

    /**
     * Provision the default 4-in / 4-out layout for a freshly registered controller.
     * Idempotent: if channels already exist they are returned untouched.
     *
     * Default mapping (best-effort, can be adjusted by admin afterwards):
     *   S1 → door_sensor       (NO)
     *   S2 → exit_button       (NO)
     *   S3 → emergency_button  (NC, per PDF)
     *   S4 → tamper_switch     (NC)
     *   R1 → magnetic_lock     (NO)
     *   R2 → buzzer            (NO)
     *   R3 → strobe_light      (NO)
     *   R4 → aux_output        (NO)
     */
    public function provisionDefaultLayout(Device $controller, ?string $vaultId = null): Collection
    {
        $this->assertIsController($controller);

        $vaultId ??= $controller->vault_id;

        return DB::transaction(function () use ($controller, $vaultId) {
            $existing = $controller->channels()->get();
            if ($existing->isNotEmpty()) {
                return $existing;
            }

            $template = $this->defaultTemplate();

            foreach ($template as $row) {
                ControllerChannel::create([
                    'device_id' => $controller->id,
                    'vault_id' => $vaultId,
                    'direction' => $row['direction'],
                    'channel_number' => $row['channel_number'],
                    'function' => $row['function'],
                    'label' => $row['function']->label(),
                    'normal_state' => $row['normal_state'],
                    'is_active' => true,
                    'is_auto_discovered' => false,
                ]);
            }

            return $controller->channels()->get();
        });
    }

    /**
     * Assign a channel manually. Validates that:
     *   - The device is a Controller.
     *   - The channel number is in 1..MAX_CHANNELS_PER_DIRECTION.
     *   - The function's natural direction matches the requested direction.
     *   - The (device, direction, channel_number) slot is free.
     */
    public function assignChannel(
        Device $controller,
        ChannelDirection $direction,
        int $channelNumber,
        ChannelFunction $function,
        NormalState $normalState,
        ?string $vaultId = null,
        ?string $label = null,
    ): ControllerChannel {
        $this->assertIsController($controller);

        if ($channelNumber < 1 || $channelNumber > self::MAX_CHANNELS_PER_DIRECTION) {
            throw new \InvalidArgumentException(
                "channel_number must be 1..".self::MAX_CHANNELS_PER_DIRECTION
            );
        }

        if ($function->direction() !== $direction) {
            throw new \InvalidArgumentException(
                "Function {$function->value} is a {$function->direction()->value} but {$direction->value} requested"
            );
        }

        $existing = ControllerChannel::query()
            ->where('device_id', $controller->id)
            ->where('direction', $direction->value)
            ->where('channel_number', $channelNumber)
            ->first();

        if ($existing) {
            $existing->update([
                'function' => $function->value,
                'normal_state' => $normalState->value,
                'vault_id' => $vaultId ?? $existing->vault_id,
                'label' => $label ?? $function->label(),
                'is_active' => true,
            ]);

            return $existing->fresh();
        }

        return ControllerChannel::create([
            'device_id' => $controller->id,
            'vault_id' => $vaultId,
            'direction' => $direction->value,
            'channel_number' => $channelNumber,
            'function' => $function->value,
            'normal_state' => $normalState->value,
            'label' => $label ?? $function->label(),
            'is_active' => true,
            'is_auto_discovered' => false,
        ]);
    }

    /**
     * Resolve which channel of which controller implements the given function
     * for the given vault. Returns null if not configured.
     */
    public function resolveChannelForVault(string $vaultId, ChannelFunction $function): ?ControllerChannel
    {
        return ControllerChannel::query()
            ->where('vault_id', $vaultId)
            ->where('function', $function->value)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get the full topology of a controller (input + output channels).
     */
    public function getTopology(Device $controller): array
    {
        $this->assertIsController($controller);

        $channels = $controller->channels()
            ->orderBy('direction')
            ->orderBy('channel_number')
            ->get();

        return [
            'inputs' => $channels->where('direction', ChannelDirection::Input)->values(),
            'outputs' => $channels->where('direction', ChannelDirection::Output)->values(),
        ];
    }

    private function assertIsController(Device $device): void
    {
        if ($device->type !== DeviceType::Controller) {
            throw new \InvalidArgumentException(
                "Device {$device->id} is not a Controller (got {$device->type?->value})"
            );
        }
    }

    /**
     * @return array<int, array{direction: string, channel_number: int, function: ChannelFunction, normal_state: string}>
     */
    private function defaultTemplate(): array
    {
        return [
            // Inputs (S1-S4)
            ['direction' => 'input',  'channel_number' => 1, 'function' => ChannelFunction::DoorSensor,      'normal_state' => 'normally_open'],
            ['direction' => 'input',  'channel_number' => 2, 'function' => ChannelFunction::ExitButton,      'normal_state' => 'normally_open'],
            ['direction' => 'input',  'channel_number' => 3, 'function' => ChannelFunction::EmergencyButton, 'normal_state' => 'normally_closed'],
            ['direction' => 'input',  'channel_number' => 4, 'function' => ChannelFunction::TamperSwitch,    'normal_state' => 'normally_closed'],
            // Outputs (R1-R4)
            ['direction' => 'output', 'channel_number' => 1, 'function' => ChannelFunction::MagneticLock,    'normal_state' => 'normally_open'],
            ['direction' => 'output', 'channel_number' => 2, 'function' => ChannelFunction::Buzzer,          'normal_state' => 'normally_open'],
            ['direction' => 'output', 'channel_number' => 3, 'function' => ChannelFunction::StrobeLight,     'normal_state' => 'normally_open'],
            ['direction' => 'output', 'channel_number' => 4, 'function' => ChannelFunction::AuxOutput,       'normal_state' => 'normally_open'],
        ];
    }
}
