<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'uuid', 'exists:devices,id'],
            'status' => ['required', 'string', 'in:healthy,degraded,critical'],
            'cpu_usage' => ['nullable', 'numeric', 'between:0,100'],
            'memory_usage' => ['nullable', 'numeric', 'between:0,100'],
            'temperature' => ['nullable', 'numeric'],
            'signal_strength' => ['nullable', 'integer'],
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => 'Device ID wajib diisi.',
            'device_id.exists' => 'Device tidak ditemukan.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status harus salah satu dari: healthy, degraded, critical.',
            'cpu_usage.between' => 'CPU usage harus antara 0 dan 100.',
            'memory_usage.between' => 'Memory usage harus antara 0 dan 100.',
            'uptime_seconds.min' => 'Uptime tidak boleh negatif.',
        ];
    }
}
