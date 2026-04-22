<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['field_id', 'title', 'start_date', 'end_date', 'position'])]
class PlannerIteration extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(PlannerField::class, 'field_id');
    }
}
