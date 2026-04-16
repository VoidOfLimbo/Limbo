<?php

use App\Models\Event;
use App\Models\Milestone;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('full planner export returns text/calendar content type', function () {
    Event::factory()->for($this->user)->count(3)->create();

    $this->actingAs($this->user)
        ->get('/planner/export/ics')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
});

it('full planner export contains a VEVENT block for each event', function () {
    Event::factory()->for($this->user)->count(2)->create(['start_at' => now()]);

    $response = $this->actingAs($this->user)->get('/planner/export/ics');

    expect(substr_count($response->content(), 'BEGIN:VEVENT'))->toBe(2);
});

it('single event export contains correct SUMMARY', function () {
    $event = Event::factory()->for($this->user)->create([
        'title' => 'My Test Event',
        'start_at' => now(),
        'end_at' => now()->addHour(),
    ]);

    $response = $this->actingAs($this->user)
        ->get("/planner/export/ics/event/{$event->id}")
        ->assertSuccessful();

    expect($response->content())
        ->toContain('SUMMARY:My Test Event')
        ->toContain('BEGIN:VEVENT');
});

it('unauthenticated request to export is redirected to login', function () {
    $this->get('/planner/export/ics')->assertRedirect('/login');
});

it('milestone export includes all events in milestone', function () {
    $milestone = Milestone::factory()->for($this->user)->create();
    Event::factory()->for($this->user)->count(3)->create([
        'milestone_id' => $milestone->id,
        'start_at' => now(),
    ]);
    // This event belongs to no milestone - should not appear
    Event::factory()->for($this->user)->create(['start_at' => now()]);

    $response = $this->actingAs($this->user)
        ->get("/planner/export/ics/milestone/{$milestone->id}")
        ->assertSuccessful();

    expect(substr_count($response->content(), 'BEGIN:VEVENT'))->toBe(3);
});
