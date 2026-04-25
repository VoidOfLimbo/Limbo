<?php

namespace App\Http\Requests\Planner;

use App\Enums\EventPriority;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\EventVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'milestone_id' => ['nullable', 'ulid', 'exists:milestones,id'],
            'parent_event_id' => ['nullable', 'ulid', 'exists:events,id'],
            'type' => ['nullable', new Enum(EventType::class)],
            'status' => ['nullable', new Enum(EventStatus::class)],
            'priority' => ['nullable', new Enum(EventPriority::class)],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'is_all_day' => ['boolean'],
            'is_milestone_marker' => ['boolean'],
            'visibility' => ['nullable', new Enum(EventVisibility::class)],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'location' => ['nullable', 'string', 'max:500'],
        ];
    }
}
