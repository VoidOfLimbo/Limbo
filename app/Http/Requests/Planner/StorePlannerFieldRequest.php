<?php

namespace App\Http\Requests\Planner;

use Illuminate\Foundation\Http\FormRequest;

class StorePlannerFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:text,number,date,single_select,multi_select,checkbox,url,person'],
            'milestone_id' => ['nullable', 'string', 'ulid', 'exists:milestones,id'],
            'options' => ['nullable', 'array'],
            'options.*.id' => ['required', 'string'],
            'options.*.name' => ['required', 'string', 'max:100'],
            'options.*.color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'settings' => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
