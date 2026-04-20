<?php

use App\Models\SessionUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('it can read active sessions with related user', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'session-abc-123',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest Test Browser',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $session = SessionUser::query()->first();

    expect($session)->not->toBeNull();
    expect($session?->user?->is($user))->toBeTrue();
});

test('it can delete active session record', function () {
    DB::table('sessions')->insert([
        'id' => 'session-delete-001',
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest Test Browser',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $session = SessionUser::query()->findOrFail('session-delete-001');

    $session->delete();

    $this->assertDatabaseMissing('sessions', [
        'id' => 'session-delete-001',
    ]);
});
