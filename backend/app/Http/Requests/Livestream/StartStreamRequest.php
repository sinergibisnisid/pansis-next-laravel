<?php

namespace App\Http\Requests\Livestream;

use Illuminate\Foundation\Http\FormRequest;

class StartStreamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('livestream.start');
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'uuid', 'exists:devices,id'],
            'vault_id' => ['nullable', 'uuid', 'exists:vaults,id'],
            'quality' => ['nullable', 'string', 'in:low,medium,high,hd'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => 'Device wajib dipilih.',
            'device_id.exists' => 'Device tidak ditemukan.',
            'vault_id.exists' => 'Vault tidak ditemukan.',
            'quality.in' => 'Kualitas stream tidak valid. Pilih: low, medium, high, atau hd.',
        ];
    }
}
