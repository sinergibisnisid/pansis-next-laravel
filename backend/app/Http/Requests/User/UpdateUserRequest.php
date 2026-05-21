<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('users.update');
    }

    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('user');

        return [
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'unique:users,username,' . $userId, 'alpha_dash'],
            'email' => ['nullable', 'email', 'unique:users,email,' . $userId],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.min' => 'Username minimal 3 karakter.',
            'username.max' => 'Username maksimal 50 karakter.',
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, dash, dan underscore.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'full_name.max' => 'Nama lengkap maksimal 255 karakter.',
            'organization_id.exists' => 'Organisasi tidak ditemukan.',
            'branch_id.exists' => 'Cabang tidak ditemukan.',
            'role.exists' => 'Role tidak ditemukan.',
        ];
    }
}
