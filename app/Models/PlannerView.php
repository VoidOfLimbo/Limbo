<?php

namespace App\Models;

use Database\Factories\PlannerViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'milestone_id', 'name', 'type', 'is_default', 'layout', 'filters', 'sorts', 'group_by', 'position'])]
class PlannerView extends Model
{
    /** @use HasFactory<PlannerViewFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'layout' => 'array',
            'filters' => 'array',
            'sorts' => 'array',
            'group_by' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }
}
