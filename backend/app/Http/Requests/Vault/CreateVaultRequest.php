<?php

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;

class CreateVaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vaults.create');
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'unique:vaults,code'],
            'type' => ['required', 'string', 'in:main,secondary,atm,safe_deposit'],
            'floor' => ['nullable', 'string'],
            'room' => ['nullable', 'string'],
            'max_session_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.exists' => 'Cabang tidak ditemukan.',
            'name.required' => 'Nama vault wajib diisi.',
            'code.required' => 'Kode vault wajib diisi.',
            'code.unique' => 'Kode vault sudah digunakan.',
            'type.required' => 'Tipe vault wajib dipilih.',
            'type.in' => 'Tipe vault tidak valid. Pilih: main, secondary, atm, atau safe_deposit.',
            'max_session_duration_minutes.min' => 'Durasi sesi minimal 1 menit.',
            'max_session_duration_minutes.max' => 'Durasi sesi maksimal 60 menit.',
        ];
    }
}
