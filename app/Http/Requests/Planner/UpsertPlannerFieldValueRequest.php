<?php

namespace App\Http\Requests\Planner;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPlannerFieldValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['present'],
        ];
    }
}
