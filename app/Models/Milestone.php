<?php

namespace App\Models;

use App\Enums\DeadlineType;
use App\Enums\DurationSource;
use App\Enums\MilestonePriority;
use App\Enums\MilestoneStatus;
use App\Enums\ProgressSource;
use Database\Factories\MilestoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'user_id', 'title', 'description', 'status', 'priority',
    'start_at', 'end_at', 'duration_source', 'deadline_type',
    'progress_source', 'progress_override', 'visibility', 'color',
])]
class Milestone extends Model
{
    /** @use HasFactory<MilestoneFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'progress_override' => 'integer',
            'status' => MilestoneStatus::class,
            'priority' => MilestonePriority::class,
            'deadline_type' => DeadlineType::class,
            'duration_source' => DurationSource::class,
            'progress_source' => ProgressSource::class,
        ];
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PlannerField::class);
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(PlannerFieldValue::class, 'item_id')
            ->where('item_type', self::class);
    }

    public function plannerViews(): HasMany
    {
        return $this->hasMany(PlannerView::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForUser(Builder $query, int|string $userId): void
    {
        $query->where('user_id', $userId);
    }

    // ── Computed ─────────────────────────────────────────────────────────────

    public function getDerivedProgressAttribute(): int
    {
        $events = $this->events()
            ->whereNotIn('status', ['cancelled', 'skipped'])
            ->whereNull('snoozed_until')
            ->orWhere('snoozed_until', '<=', now())
            ->get();

        $total = $events->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $events->where('status', 'completed')->count();

        return (int) round(($completed / $total) * 100);
    }

    public function getProgressAttribute(): int
    {
        if ($this->progress_source === ProgressSource::Manual && $this->progress_override !== null) {
            return $this->progress_override;
        }

        return $this->derived_progress;
    }

    public function isBreached(): bool
    {
        if ($this->deadline_type !== DeadlineType::Hard || $this->end_at === null) {
            return false;
        }

        return $this->events()
            ->whereNotNull('end_at')
            ->where('end_at', '>', $this->end_at)
            ->exists();
    }

    public function derivedStartAt(): ?Carbon
    {
        $min = $this->events()->min('start_at');

        return $min ? Carbon::parse($min) : null;
    }

    public function derivedEndAt(): ?Carbon
    {
        $max = $this->events()->max('end_at');

        return $max ? Carbon::parse($max) : null;
    }

    public function recalculateDerivedDates(): void
    {
        if ($this->duration_source !== DurationSource::Derived) {
            return;
        }

        if ($this->deadline_type === DeadlineType::Hard) {
            return;
        }

        $this->start_at = $this->derivedStartAt();
        $this->end_at = $this->derivedEndAt();
        $this->saveQuietly();
    }
}
