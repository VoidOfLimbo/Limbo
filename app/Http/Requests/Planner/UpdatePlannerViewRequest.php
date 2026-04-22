<?php

namespace App\Http\Requests\Planner;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlannerViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'string', 'in:list,table,board,roadmap'],
            'is_default' => ['sometimes', 'boolean'],
            'layout' => ['sometimes', 'nullable', 'array'],
            'filters' => ['sometimes', 'nullable', 'array'],
            'sorts' => ['sometimes', 'nullable', 'array'],
            'group_by' => ['sometimes', 'nullable', 'array'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
