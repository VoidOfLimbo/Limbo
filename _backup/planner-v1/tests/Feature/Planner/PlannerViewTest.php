<?php

use App\Models\PlannerView;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can list planner views', function () {
    PlannerView::factory()->for($this->user)->count(2)->create();

    $this->actingAs($this->user)
        ->getJson('/planner/views')
        ->assertSuccessful()
        ->assertJsonCount(2);
});

it('does not return another user views', function () {
    $other = User::factory()->create();
    PlannerView::factory()->for($other)->count(3)->create();

    $this->actingAs($this->user)
        ->getJson('/planner/views')
        ->assertSuccessful()
        ->assertJsonCount(0);
});

it('can create a named view', function () {
    $this->actingAs($this->user)
        ->post('/planner/views', [
            'name' => 'My Sprint',
            'type' => 'board',
        ])
        ->assertRedirect();

    expect(PlannerView::where('user_id', $this->user->id)->where('name', 'My Sprint')->exists())->toBeTrue();
});

it('validates view type on creation', function () {
    $this->actingAs($this->user)
        ->post('/planner/views', ['name' => 'Test', 'type' => 'invalid'])
        ->assertInvalid(['type']);
});

it('can update a view name and type', function () {
    $view = PlannerView::factory()->for($this->user)->create(['name' => 'Old', 'type' => 'list']);

    $this->actingAs($this->user)
        ->put("/planner/views/{$view->id}", ['name' => 'Updated', 'type' => 'table'])
        ->assertRedirect();

    $view->refresh();
    expect($view->name)->toBe('Updated')
        ->and($view->type)->toBe('table');
});

it('can delete a view', function () {
    $view = PlannerView::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->delete("/planner/views/{$view->id}")
        ->assertRedirect();

    expect(PlannerView::find($view->id))->toBeNull();
});

it('cannot modify another user view', function () {
    $other = User::factory()->create();
    $view = PlannerView::factory()->for($other)->create();

    $this->actingAs($this->user)
        ->put("/planner/views/{$view->id}", ['name' => 'Stolen'])
        ->assertForbidden();
});

it('can activate a view as default and clears other defaults', function () {
    $viewA = PlannerView::factory()->for($this->user)->default()->create(['milestone_id' => null]);
    $viewB = PlannerView::factory()->for($this->user)->create(['milestone_id' => null]);

    $this->actingAs($this->user)
        ->post("/planner/views/{$viewB->id}/activate")
        ->assertRedirect();

    expect($viewB->fresh()->is_default)->toBeTrue()
        ->and($viewA->fresh()->is_default)->toBeFalse();
});
