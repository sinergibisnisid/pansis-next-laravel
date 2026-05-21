<?php

namespace App\Http\Requests\Alarm;

use Illuminate\Foundation\Http\FormRequest;

class ResolveAlarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('alarms.resolve');
    }

    public function rules(): array
    {
        return [
            'resolution_notes' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution_notes.required' => 'Catatan resolusi wajib diisi.',
            'resolution_notes.max' => 'Catatan resolusi maksimal 2000 karakter.',
        ];
    }
}
