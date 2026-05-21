<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reports.generate');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:audit,activity,access,alarm,maintenance,daily,weekly,monthly'],
            'format' => ['required', 'string', 'in:pdf,excel,csv'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'parameters' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul laporan wajib diisi.',
            'title.max' => 'Judul laporan maksimal 255 karakter.',
            'type.required' => 'Tipe laporan wajib dipilih.',
            'type.in' => 'Tipe laporan tidak valid.',
            'format.required' => 'Format laporan wajib dipilih.',
            'format.in' => 'Format laporan harus: pdf, excel, atau csv.',
            'branch_id.exists' => 'Cabang tidak ditemukan.',
            'period_start.required' => 'Tanggal mulai wajib diisi.',
            'period_start.date' => 'Format tanggal mulai tidak valid.',
            'period_end.required' => 'Tanggal akhir wajib diisi.',
            'period_end.date' => 'Format tanggal akhir tidak valid.',
            'period_end.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
        ];
    }
}
