<?php

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;

class CloseVaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vaults.close');
    }

    public function rules(): array
    {
        return [
            'vault_id' => ['required', 'uuid', 'exists:vaults,id'],
            'session_id' => ['required', 'uuid', 'exists:vault_sessions,id'],
            'close_reason' => ['required', 'string', 'in:push_button,manual,timeout,emergency'],
        ];
    }

    public function messages(): array
    {
        return [
            'vault_id.required' => 'Vault wajib dipilih.',
            'vault_id.exists' => 'Vault tidak ditemukan.',
            'session_id.required' => 'Session ID wajib diisi.',
            'session_id.exists' => 'Session tidak ditemukan.',
            'close_reason.required' => 'Alasan penutupan wajib diisi.',
            'close_reason.in' => 'Alasan penutupan tidak valid. Pilih: push_button, manual, timeout, atau emergency.',
        ];
    }
}
