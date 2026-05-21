<?php

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;

class OpenVaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vaults.open');
    }

    public function rules(): array
    {
        return [
            'vault_id' => ['required', 'uuid', 'exists:vaults,id'],
            'device_id' => ['required', 'uuid', 'exists:devices,id'],
            'access_type' => ['required', 'string', 'in:fingerprint,manual_override,emergency,maintenance'],
            'fingerprint_device_id' => ['required_if:access_type,fingerprint', 'uuid', 'exists:fingerprint_devices,id'],
            'confidence_score' => ['nullable', 'integer', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'vault_id.required' => 'Vault wajib dipilih.',
            'vault_id.exists' => 'Vault tidak ditemukan.',
            'device_id.required' => 'Device wajib dipilih.',
            'device_id.exists' => 'Device tidak ditemukan.',
            'access_type.required' => 'Tipe akses wajib dipilih.',
            'access_type.in' => 'Tipe akses tidak valid. Pilih: fingerprint, manual_override, emergency, atau maintenance.',
            'fingerprint_device_id.required_if' => 'Fingerprint device wajib dipilih untuk akses fingerprint.',
            'fingerprint_device_id.exists' => 'Fingerprint device tidak ditemukan.',
            'confidence_score.between' => 'Confidence score harus antara 0 dan 100.',
        ];
    }
}
