<?php

namespace App\Http\Requests\Alarm;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeAlarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('alarms.acknowledge');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
