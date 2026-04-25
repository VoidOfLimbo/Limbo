<?php

namespace App\Http\Requests\Planner;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlannerFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'options' => ['sometimes', 'nullable', 'array'],
            'options.*.id' => ['required', 'string'],
            'options.*.name' => ['required', 'string', 'max:100'],
            'options.*.color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
