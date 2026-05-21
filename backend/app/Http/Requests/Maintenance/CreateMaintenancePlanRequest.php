<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class CreateMaintenancePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('maintenance.create');
    }

    public function rules(): array
    {
        return [
            'vault_id' => ['nullable', 'uuid', 'exists:vaults,id'],
            'device_id' => ['nullable', 'uuid', 'exists:devices,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:cleaning,lubrication,inspection,repair,calibration,replacement'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'frequency' => ['nullable', 'string', 'in:daily,weekly,monthly,quarterly,yearly,once'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'due_date' => ['nullable', 'date', 'after_or_equal:scheduled_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'vault_id.exists' => 'Vault tidak ditemukan.',
            'device_id.exists' => 'Device tidak ditemukan.',
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.exists' => 'Cabang tidak ditemukan.',
            'assigned_to.exists' => 'User yang ditugaskan tidak ditemukan.',
            'title.required' => 'Judul maintenance wajib diisi.',
            'title.max' => 'Judul maintenance maksimal 255 karakter.',
            'type.required' => 'Tipe maintenance wajib dipilih.',
            'type.in' => 'Tipe maintenance tidak valid.',
            'priority.required' => 'Prioritas wajib dipilih.',
            'priority.in' => 'Prioritas tidak valid. Pilih: low, medium, high, atau urgent.',
            'frequency.in' => 'Frekuensi tidak valid.',
            'scheduled_date.required' => 'Tanggal jadwal wajib diisi.',
            'scheduled_date.after_or_equal' => 'Tanggal jadwal tidak boleh di masa lalu.',
            'scheduled_time.date_format' => 'Format waktu harus HH:MM.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo harus sama atau setelah tanggal jadwal.',
        ];
    }
}
