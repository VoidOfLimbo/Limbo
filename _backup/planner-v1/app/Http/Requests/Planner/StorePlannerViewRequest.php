<?php

namespace App\Http\Requests\Planner;

use Illuminate\Foundation\Http\FormRequest;

class StorePlannerViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:list,table,board,roadmap'],
            'milestone_id' => ['nullable', 'string', 'ulid', 'exists:milestones,id'],
            'is_default' => ['boolean'],
            'layout' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
            'sorts' => ['nullable', 'array'],
            'group_by' => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
