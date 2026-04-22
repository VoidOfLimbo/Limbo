<?php

namespace App\Models;

use Database\Factories\PlannerFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'milestone_id', 'name', 'type', 'options', 'settings', 'position', 'is_system'])]
class PlannerField extends Model
{
    /** @use HasFactory<PlannerFieldFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'settings' => 'array',
            'is_system' => 'boolean',
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

    public function values(): HasMany
    {
        return $this->hasMany(PlannerFieldValue::class, 'field_id');
    }

    public function iterations(): HasMany
    {
        return $this->hasMany(PlannerIteration::class, 'field_id')->orderBy('start_date');
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    public function scopeForMilestone(Builder $query, ?string $milestoneId): Builder
    {
        return $query->where(function ($q) use ($milestoneId) {
            $q->whereNull('milestone_id')->orWhere('milestone_id', $milestoneId);
        });
    }
}
