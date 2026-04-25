<?php

use App\Models\PlannerField;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can list planner fields for a user', function () {
    PlannerField::factory()->for($this->user)->count(3)->create();

    $this->actingAs($this->user)
        ->getJson('/planner/fields')
        ->assertSuccessful()
        ->assertJsonCount(3);
});

it('does not return another user fields', function () {
    $other = User::factory()->create();
    PlannerField::factory()->for($other)->count(2)->create();

    $this->actingAs($this->user)
        ->getJson('/planner/fields')
        ->assertSuccessful()
        ->assertJsonCount(0);
});

it('can create a custom field', function () {
    $this->actingAs($this->user)
        ->post('/planner/fields', [
            'name' => 'Effort',
            'type' => 'number',
        ])
        ->assertRedirect();

    expect(PlannerField::where('user_id', $this->user->id)->where('name', 'Effort')->exists())->toBeTrue();
});

it('validates field type on creation', function () {
    $this->actingAs($this->user)
        ->post('/planner/fields', ['name' => 'Bad', 'type' => 'invalid_type'])
        ->assertInvalid(['type']);
});

it('can update a custom field name', function () {
    $field = PlannerField::factory()->for($this->user)->create(['name' => 'Old Name']);

    $this->actingAs($this->user)
        ->put("/planner/fields/{$field->id}", ['name' => 'New Name'])
        ->assertRedirect();

    expect($field->fresh()->name)->toBe('New Name');
});

it('cannot update a system field', function () {
    $field = PlannerField::factory()->for($this->user)->system()->create(['name' => 'Title', 'type' => 'text']);

    $this->actingAs($this->user)
        ->put("/planner/fields/{$field->id}", ['name' => 'Hacked'])
        ->assertForbidden();
});

it('can delete a custom field', function () {
    $field = PlannerField::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->delete("/planner/fields/{$field->id}")
        ->assertRedirect();

    expect(PlannerField::find($field->id))->toBeNull();
});

it('cannot delete a system field', function () {
    $field = PlannerField::factory()->for($this->user)->system()->create(['name' => 'Status', 'type' => 'single_select']);

    $this->actingAs($this->user)
        ->delete("/planner/fields/{$field->id}")
        ->assertForbidden();
});

it('cannot modify another user field', function () {
    $other = User::factory()->create();
    $field = PlannerField::factory()->for($other)->create();

    $this->actingAs($this->user)
        ->put("/planner/fields/{$field->id}", ['name' => 'Stolen'])
        ->assertForbidden();
});

it('can add an option to a single-select field', function () {
    $field = PlannerField::factory()->for($this->user)->singleSelect()->create();
    $initialCount = count($field->options);

    $this->actingAs($this->user)
        ->post("/planner/fields/{$field->id}/options", ['name' => 'Option C', 'color' => '#3b82f6'])
        ->assertRedirect();

    expect(count($field->fresh()->options))->toBe($initialCount + 1);
});

it('can remove an option from a single-select field', function () {
    $field = PlannerField::factory()->for($this->user)->singleSelect()->create();
    $optionId = $field->options[0]['id'];

    $this->actingAs($this->user)
        ->delete("/planner/fields/{$field->id}/options/{$optionId}")
        ->assertRedirect();

    $remaining = collect($field->fresh()->options)->pluck('id');
    expect($remaining->contains($optionId))->toBeFalse();
});
