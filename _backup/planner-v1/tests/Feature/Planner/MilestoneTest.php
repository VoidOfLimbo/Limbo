<?php

use App\Enums\DurationSource;
use App\Enums\ProgressSource;
use App\Models\Milestone;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can create a milestone', function () {
    $this->actingAs($this->user)
        ->post('/milestones', [
            'title' => 'Launch my freelance business',
        ])
        ->assertRedirect();

    expect(Milestone::where('user_id', $this->user->id)->where('title', 'Launch my freelance business')->exists())->toBeTrue();
});

it('can update a milestone', function () {
    $milestone = Milestone::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->put("/milestones/{$milestone->id}", ['title' => 'Updated Title'])
        ->assertRedirect();

    expect($milestone->fresh()->title)->toBe('Updated Title');
});

it('can soft-delete a milestone', function () {
    $milestone = Milestone::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->delete("/milestones/{$milestone->id}")
        ->assertRedirect();

    expect(Milestone::find($milestone->id))->toBeNull()
        ->and(Milestone::withTrashed()->find($milestone->id))->not->toBeNull();
});

it('derived end_at extends when a soft milestone child event end_at grows', function () {
    $milestone = Milestone::factory()->for($this->user)->softDeadline()->create([
        'duration_source' => DurationSource::Derived,
        'start_at' => now()->addDays(1),
        'end_at' => now()->addDays(10),
    ]);

    $event = $milestone->events()->create([
        'user_id' => $this->user->id,
        'title' => 'Big event',
        'start_at' => now()->addDays(1),
        'end_at' => now()->addDays(30),
        'status' => 'upcoming',
        'priority' => 'medium',
    ]);

    $milestone->recalculateDerivedDates();

    expect($milestone->fresh()->end_at->toDateString())->toBe($event->end_at->toDateString());
});

it('derived end_at does not extend for hard deadline milestone', function () {
    $originalEnd = now()->addDays(10);

    $milestone = Milestone::factory()->for($this->user)->hardDeadline()->create([
        'duration_source' => DurationSource::Derived,
        'end_at' => $originalEnd,
    ]);

    // recalculateDerivedDates should not run on hard milestone
    $milestone->recalculateDerivedDates();

    // end_at unchanged because duration_source check skips hard deadlines only if we make that a rule
    // The key test: the controller prevents updating end_at on hard milestones
    $this->actingAs($this->user)
        ->put("/milestones/{$milestone->id}", ['end_at' => now()->addDays(50)->toISOString()])
        ->assertRedirect();

    expect($milestone->fresh()->end_at->toDateString())->toBe($originalEnd->toDateString());
});

it('detects hard milestone breach correctly', function () {
    $milestone = Milestone::factory()->for($this->user)->hardDeadline()->create([
        'end_at' => now()->addDays(10),
    ]);

    $milestone->events()->create([
        'user_id' => $this->user->id,
        'title' => 'Overflowing event',
        'start_at' => now()->addDays(5),
        'end_at' => now()->addDays(20),
        'status' => 'upcoming',
        'priority' => 'medium',
    ]);

    expect($milestone->isBreached())->toBeTrue();
});

it('derives progress correctly from completed events', function () {
    $milestone = Milestone::factory()->for($this->user)->create();

    $milestone->events()->createMany([
        ['user_id' => $this->user->id, 'title' => 'A', 'status' => 'completed', 'priority' => 'medium'],
        ['user_id' => $this->user->id, 'title' => 'B', 'status' => 'completed', 'priority' => 'medium'],
        ['user_id' => $this->user->id, 'title' => 'C', 'status' => 'upcoming', 'priority' => 'medium'],
        ['user_id' => $this->user->id, 'title' => 'D', 'status' => 'cancelled', 'priority' => 'medium'], // excluded
    ]);

    // 2 completed out of 3 eligible = 67%
    expect($milestone->derived_progress)->toBe(67);
});

it('returns manual progress override when set', function () {
    $milestone = Milestone::factory()->for($this->user)->create([
        'progress_source' => ProgressSource::Manual,
        'progress_override' => 42,
    ]);

    expect($milestone->progress)->toBe(42);
});

it('cannot view another user\'s milestone', function () {
    $other = User::factory()->create();
    $milestone = Milestone::factory()->for($other)->create();

    $this->actingAs($this->user)
        ->get("/milestones/{$milestone->id}")
        ->assertForbidden();
});
