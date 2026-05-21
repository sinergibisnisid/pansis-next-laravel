<?php

namespace App\Http\Requests\WorkingTime;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkingTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('working-times.create');
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'vault_id' => ['nullable', 'uuid', 'exists:vaults,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:recurring,specific_date,holiday'],
            'day_of_week' => ['required_if:type,recurring', 'integer', 'between:0,6'],
            'specific_date' => ['required_if:type,specific_date', 'required_if:type,holiday', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['nullable', 'string', 'timezone'],
            'is_holiday' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.exists' => 'Cabang tidak ditemukan.',
            'vault_id.exists' => 'Vault tidak ditemukan.',
            'name.required' => 'Nama jadwal wajib diisi.',
            'name.max' => 'Nama jadwal maksimal 255 karakter.',
            'type.required' => 'Tipe jadwal wajib dipilih.',
            'type.in' => 'Tipe jadwal tidak valid. Pilih: recurring, specific_date, atau holiday.',
            'day_of_week.required_if' => 'Hari dalam minggu wajib diisi untuk tipe recurring.',
            'day_of_week.between' => 'Hari dalam minggu harus antara 0 (Minggu) dan 6 (Sabtu).',
            'specific_date.required_if' => 'Tanggal spesifik wajib diisi untuk tipe ini.',
            'specific_date.date' => 'Format tanggal tidak valid.',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'start_time.date_format' => 'Format waktu mulai harus HH:MM.',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.date_format' => 'Format waktu selesai harus HH:MM.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'timezone.timezone' => 'Timezone tidak valid.',
        ];
    }
}
