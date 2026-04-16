<?php

use App\Models\Event;
use App\Models\Milestone;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can create an event with a milestone', function () {
    $milestone = Milestone::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->post('/events', [
            'title' => 'Build portfolio site',
            'milestone_id' => $milestone->id,
            'status' => 'upcoming',
            'priority' => 'high',
        ])
        ->assertRedirect();

    expect(Event::where('user_id', $this->user->id)->where('title', 'Build portfolio site')->exists())->toBeTrue();
});

it('can create a backlog event with no milestone', function () {
    $this->actingAs($this->user)
        ->post('/events', [
            'title' => 'Backlog task',
            'status' => 'draft',
            'priority' => 'low',
        ])
        ->assertRedirect();

    expect(Event::where('user_id', $this->user->id)->where('milestone_id', null)->where('title', 'Backlog task')->exists())->toBeTrue();
});

it('can snooze an event and increments snooze_count', function () {
    $event = Event::factory()->for($this->user)->create(['snooze_count' => 0]);
    $until = now()->addHours(4)->toISOString();

    $this->actingAs($this->user)
        ->post("/events/{$event->id}/snooze", ['snoozed_until' => $until])
        ->assertRedirect();

    $fresh = $event->fresh();
    expect($fresh->isSnoozed())->toBeTrue()
        ->and($fresh->snooze_count)->toBe(1);
});

it('active scope excludes snoozed events', function () {
    Event::factory()->for($this->user)->create(['title' => 'Active']);
    Event::factory()->for($this->user)->snoozed()->create(['title' => 'Snoozed']);

    $active = Event::forUser($this->user->id)->active()->pluck('title');

    expect($active)->toContain('Active')
        ->and($active)->not->toContain('Snoozed');
});

it('snoozed scope returns only snoozed events', function () {
    Event::factory()->for($this->user)->create(['title' => 'Active']);
    Event::factory()->for($this->user)->snoozed()->create(['title' => 'Snoozed']);

    $snoozed = Event::forUser($this->user->id)->snoozed()->pluck('title');

    expect($snoozed)->toContain('Snoozed')
        ->and($snoozed)->not->toContain('Active');
});

it('isBreachingMilestone returns true when event end_at exceeds hard milestone end_at', function () {
    $milestone = Milestone::factory()->for($this->user)->hardDeadline()->create([
        'end_at' => now()->addDays(10),
    ]);

    $event = Event::factory()->for($this->user)->create([
        'milestone_id' => $milestone->id,
        'end_at' => now()->addDays(20),
    ]);

    expect($event->load('milestone')->isBreachingMilestone())->toBeTrue();
});

it('cannot update another user\'s event', function () {
    $other = User::factory()->create();
    $event = Event::factory()->for($other)->create();

    $this->actingAs($this->user)
        ->put("/events/{$event->id}", ['title' => 'Hacked'])
        ->assertForbidden();
});
