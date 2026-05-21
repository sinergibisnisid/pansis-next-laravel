<?php

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vaults.update');
    }

    public function rules(): array
    {
        $vaultId = $this->route('id') ?? $this->route('vault');

        return [
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'unique:vaults,code,' . $vaultId],
            'type' => ['nullable', 'string', 'in:main,secondary,atm,safe_deposit'],
            'floor' => ['nullable', 'string'],
            'room' => ['nullable', 'string'],
            'max_session_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.exists' => 'Cabang tidak ditemukan.',
            'code.unique' => 'Kode vault sudah digunakan.',
            'type.in' => 'Tipe vault tidak valid. Pilih: main, secondary, atm, atau safe_deposit.',
            'max_session_duration_minutes.min' => 'Durasi sesi minimal 1 menit.',
            'max_session_duration_minutes.max' => 'Durasi sesi maksimal 60 menit.',
        ];
    }
}
