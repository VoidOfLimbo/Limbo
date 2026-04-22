<?php

use App\Models\Event;
use App\Models\PlannerField;
use App\Models\PlannerFieldValue;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can upsert a field value on an event', function () {
    $field = PlannerField::factory()->for($this->user)->create(['type' => 'text']);
    $event = Event::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->put("/planner/field-values/{$field->id}/event/{$event->id}", ['value' => 'some text'])
        ->assertRedirect();

    expect(PlannerFieldValue::where('field_id', $field->id)->where('item_id', $event->id)->exists())->toBeTrue();
});

it('updates existing field value on second upsert', function () {
    $field = PlannerField::factory()->for($this->user)->create(['type' => 'text']);
    $event = Event::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->put("/planner/field-values/{$field->id}/event/{$event->id}", ['value' => 'first'])
        ->assertRedirect();

    $this->actingAs($this->user)
        ->put("/planner/field-values/{$field->id}/event/{$event->id}", ['value' => 'second'])
        ->assertRedirect();

    expect(PlannerFieldValue::where('field_id', $field->id)->where('item_id', $event->id)->count())->toBe(1)
        ->and(PlannerFieldValue::where('field_id', $field->id)->where('item_id', $event->id)->first()->value)->toBe('second');
});

it('can clear a field value', function () {
    $field = PlannerField::factory()->for($this->user)->create(['type' => 'text']);
    $event = Event::factory()->for($this->user)->create();

    PlannerFieldValue::factory()->create([
        'field_id' => $field->id,
        'item_id' => $event->id,
        'item_type' => Event::class,
        'value' => 'deletable',
    ]);

    $this->actingAs($this->user)
        ->delete("/planner/field-values/{$field->id}/event/{$event->id}")
        ->assertRedirect();

    expect(PlannerFieldValue::where('field_id', $field->id)->where('item_id', $event->id)->exists())->toBeFalse();
});

it('cannot upsert value on another user event', function () {
    $field = PlannerField::factory()->for($this->user)->create(['type' => 'text']);
    $otherUser = User::factory()->create();
    $event = Event::factory()->for($otherUser)->create();

    $this->actingAs($this->user)
        ->put("/planner/field-values/{$field->id}/event/{$event->id}", ['value' => 'hacked'])
        ->assertNotFound();
});

it('rejects unknown item type', function () {
    $field = PlannerField::factory()->for($this->user)->create(['type' => 'text']);

    $this->actingAs($this->user)
        ->put("/planner/field-values/{$field->id}/unknown/some-id", ['value' => 'x'])
        ->assertUnprocessable();
});
