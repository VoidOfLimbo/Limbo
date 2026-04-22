<?php

use App\Models\PlannerField;
use App\Models\PlannerView;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('renders the planner page for an authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/planner')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Planner/Index'));
});

it('includes milestones and activeMilestoneId as eager props', function () {
    $this->actingAs($this->user)
        ->get('/planner')
        ->assertInertia(fn ($page) => $page
            ->has('milestones')
            ->has('activeMilestoneId')
            ->has('showingDashboard')
            ->has('filters'),
        );
});

it('fields endpoint returns only the authenticated user fields', function () {
    PlannerField::factory()->for($this->user)->count(2)->create();
    PlannerField::factory()->for(User::factory()->create())->count(5)->create();

    $this->actingAs($this->user)
        ->getJson('/planner/fields')
        ->assertSuccessful()
        ->assertJsonCount(2);
});

it('savedViews endpoint returns only the authenticated user views', function () {
    PlannerView::factory()->for($this->user)->count(3)->create();
    PlannerView::factory()->for(User::factory()->create())->count(2)->create();

    $this->actingAs($this->user)
        ->getJson('/planner/views')
        ->assertSuccessful()
        ->assertJsonCount(3);
});

it('redirects unauthenticated users', function () {
    $this->get('/planner')->assertRedirect('/login');
});
