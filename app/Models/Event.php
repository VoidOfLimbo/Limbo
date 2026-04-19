<?php

namespace App\Models;

use App\Enums\EventPriority;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\EventVisibility;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'milestone_id', 'parent_event_id', 'title', 'description',
    'type', 'status', 'priority', 'start_at', 'end_at', 'is_all_day',
    'is_milestone_marker', 'recurrence_rule', 'recurrence_ends_at',
    'recurrence_count', 'visibility', 'color', 'location',
    'snoozed_until', 'snooze_count', 'sort_order',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'snoozed_until' => 'datetime',
            'recurrence_ends_at' => 'datetime',
            'is_all_day' => 'boolean',
            'is_milestone_marker' => 'boolean',
            'recurrence_rule' => 'array',
            'status' => EventStatus::class,
            'type' => EventType::class,
            'priority' => EventPriority::class,
            'visibility' => EventVisibility::class,
        ];
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'parent_event_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Event::class, 'parent_event_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(EventReminder::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(EventOccurrence::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(EventDependency::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereNull('snoozed_until')
                ->orWhere('snoozed_until', '<=', now());
        });
    }

    public function scopeSnoozed(Builder $query): void
    {
        $query->where('snoozed_until', '>', now());
    }

    public function scopeForMilestone(Builder $query, string $milestoneId): void
    {
        $query->where('milestone_id', $milestoneId);
    }

    public function scopeBacklog(Builder $query): void
    {
        $query->whereNull('milestone_id');
    }

    public function scopeForUser(Builder $query, int|string $userId): void
    {
        $query->where('user_id', $userId);
    }

    // ── Methods ──────────────────────────────────────────────────────────────

    public function isSnoozed(): bool
    {
        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }

    public function isBreachingMilestone(): bool
    {
        if ($this->milestone === null || $this->end_at === null) {
            return false;
        }

        if ($this->milestone->deadline_type->value !== 'hard') {
            return false;
        }

        if ($this->milestone->end_at === null) {
            return false;
        }

        return $this->end_at->greaterThan($this->milestone->end_at);
    }
}
