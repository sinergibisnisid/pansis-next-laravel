<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('devices.create');
    }

    public function rules(): array
    {
        return [
            'vault_id' => ['nullable', 'uuid', 'exists:vaults,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'unique:devices,serial_number'],
            'type' => ['required', 'string', 'in:controller,fingerprint_scanner,camera,sensor,buzzer,lock'],
            'ip_address' => ['nullable', 'ip'],
            'mac_address' => ['nullable', 'string', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'firmware_version' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.exists' => 'Cabang tidak ditemukan.',
            'name.required' => 'Nama perangkat wajib diisi.',
            'serial_number.required' => 'Nomor seri wajib diisi.',
            'serial_number.unique' => 'Nomor seri sudah terdaftar.',
            'type.required' => 'Tipe perangkat wajib dipilih.',
            'type.in' => 'Tipe perangkat tidak valid.',
            'ip_address.ip' => 'Format IP address tidak valid.',
            'mac_address.regex' => 'Format MAC address tidak valid (contoh: AA:BB:CC:DD:EE:FF).',
        ];
    }
}
