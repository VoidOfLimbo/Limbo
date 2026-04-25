<?php

namespace App\Http\Requests\Planner;

use App\Models\Event;
use App\Models\Milestone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'taggable_type' => ['required', 'string', Rule::in(['milestone', 'event'])],
            'taggable_id' => ['required', 'string'],
        ];
    }

    /**
     * @return class-string<Model>
     */
    public function taggableModelClass(): string
    {
        return match ($this->validated('taggable_type')) {
            'milestone' => Milestone::class,
            'event' => Event::class,
        };
    }
}
