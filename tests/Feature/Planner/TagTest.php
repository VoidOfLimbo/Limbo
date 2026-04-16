<?php

use App\Models\Event;
use App\Models\Milestone;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can create a tag', function () {
    $this->actingAs($this->user)
        ->post('/tags', ['name' => 'work', 'color' => '#3b82f6'])
        ->assertRedirect();

    expect(Tag::where('user_id', $this->user->id)->where('name', 'work')->exists())->toBeTrue();
});

it('tag name is unique per user', function () {
    Tag::factory()->for($this->user)->create(['name' => 'work']);

    $this->actingAs($this->user)
        ->post('/tags', ['name' => 'work'])
        ->assertSessionHasErrors('name');
});

it('can attach a tag to an event', function () {
    $tag = Tag::factory()->for($this->user)->create();
    $event = Event::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->post("/tags/{$tag->id}/attach", [
            'taggable_type' => 'event',
            'taggable_id' => $event->id,
        ])
        ->assertRedirect();

    expect($event->fresh()->tags()->where('tags.id', $tag->id)->exists())->toBeTrue();
});

it('can attach a tag to a milestone', function () {
    $tag = Tag::factory()->for($this->user)->create();
    $milestone = Milestone::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->post("/tags/{$tag->id}/attach", [
            'taggable_type' => 'milestone',
            'taggable_id' => $milestone->id,
        ])
        ->assertRedirect();

    expect($milestone->fresh()->tags()->where('tags.id', $tag->id)->exists())->toBeTrue();
});

it('can detach a tag from an event', function () {
    $tag = Tag::factory()->for($this->user)->create();
    $event = Event::factory()->for($this->user)->create();
    $event->tags()->attach($tag->id);

    $this->actingAs($this->user)
        ->delete("/tags/{$tag->id}/detach", [
            'taggable_type' => 'event',
            'taggable_id' => $event->id,
        ])
        ->assertRedirect();

    expect($event->fresh()->tags()->where('tags.id', $tag->id)->exists())->toBeFalse();
});
