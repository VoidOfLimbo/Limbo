<?php

namespace App\Http\Requests\Planner;

use App\Enums\DeadlineType;
use App\Enums\DurationSource;
use App\Enums\MilestonePriority;
use App\Enums\MilestoneStatus;
use App\Enums\ProgressSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['nullable', new Enum(MilestoneStatus::class)],
            'priority' => ['nullable', new Enum(MilestonePriority::class)],
            'deadline_type' => ['nullable', new Enum(DeadlineType::class)],
            'duration_source' => ['nullable', new Enum(DurationSource::class)],
            'start_at' => ['nullable', 'date', Rule::requiredIf(fn () => $this->input('duration_source') === DurationSource::Manual->value)],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'progress_source' => ['nullable', new Enum(ProgressSource::class)],
            'progress_override' => ['nullable', 'integer', 'min:0', 'max:100', Rule::requiredIf(fn () => $this->input('progress_source') === ProgressSource::Manual->value)],
            'visibility' => ['nullable', Rule::in(['private', 'shared'])],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
