<?php

use App\Models\SocialAccount;
use App\Models\User;

test('social account links to existing user relationship', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $account = SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => '123456789',
    ]);

    expect($user->fresh()->socialAccounts)->toHaveCount(1);
    expect($account->user->id)->toBe($user->id);
});

test('social account stores provider credentials correctly', function () {
    $user = User::factory()->create();

    $account = SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => '987654321',
        'provider_token' => 'access-token',
        'provider_refresh_token' => 'refresh-token',
    ]);

    expect($account->provider)->toBe('google');
    expect($account->provider_id)->toBe('987654321');
    expect($account->user->id)->toBe($user->id);
});

test('a user can have multiple social accounts from different providers', function () {
    $user = User::factory()->create();

    SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'google']);
    SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'microsoft']);

    expect($user->fresh()->socialAccounts)->toHaveCount(2);
});

test('deleting a user cascades to their social accounts', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->create(['user_id' => $user->id]);

    expect(SocialAccount::count())->toBe(1);

    $user->delete();

    expect(SocialAccount::count())->toBe(0);
});
