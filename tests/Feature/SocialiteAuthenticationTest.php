<?php

use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

test('it redirects to no auth when email is not in whitelist', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'provider-1',
        'name' => 'Non Whitelist',
        'email' => 'not-allowed@example.com',
    ]));

    $response = $this->get(route('auth.social.callback', ['provider' => 'google']));

    $response->assertRedirect(route('auth.no-access'));
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('it creates a new user and logs in when email is in whitelist', function () {
    Whitelist::query()->create([
        'email' => 'allowed@example.com',
        'nama' => 'Allowed User',
        'instansi' => 'Acme Corp',
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'provider-2',
        'name' => 'Allowed From Provider',
        'email' => 'allowed@example.com',
        'avatar' => 'https://example.com/avatar.png',
    ]));

    $response = $this->get(route('auth.social.callback', ['provider' => 'google']));

    $user = User::query()->where('email', 'allowed@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user?->instansi)->toBe('Acme Corp');
    expect($user?->provider)->toBe('google');
    expect($user?->provider_id)->toBe('provider-2');
    expect($user?->role)->toBe('member');

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('it logs in existing user without updating profile fields', function () {
    Whitelist::query()->create([
        'email' => 'existing@example.com',
        'nama' => 'Existing User',
        'instansi' => 'Instansi Baru',
    ]);

    $existingUser = User::query()->create([
        'name' => 'Original Name',
        'email' => 'existing@example.com',
        'password' => null,
        'instansi' => 'Instansi Lama',
        'provider' => 'google',
        'provider_id' => 'provider-old',
        'role' => 'member',
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'provider-new',
        'name' => 'Changed Name',
        'email' => 'existing@example.com',
    ]));

    $response = $this->get(route('auth.social.callback', ['provider' => 'google']));

    $existingUser->refresh();

    expect($existingUser->name)->toBe('Original Name');
    expect($existingUser->provider_id)->toBe('provider-old');
    expect($existingUser->instansi)->toBe('Instansi Baru');

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($existingUser);

});

test('it returns not found when provider is not allowed', function () {
    $response = $this->get(route('auth.social.redirect', ['provider' => 'github']));

    $response->assertNotFound();
});
