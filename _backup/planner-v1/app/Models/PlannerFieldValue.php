<?php

namespace App\Models;

use Database\Factories\PlannerFieldValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['field_id', 'item_id', 'item_type', 'value'])]
class PlannerFieldValue extends Model
{
    /** @use HasFactory<PlannerFieldValueFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(PlannerField::class, 'field_id');
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}
