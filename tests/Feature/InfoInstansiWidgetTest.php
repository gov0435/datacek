<?php

use App\Filament\App\Widgets\InfoInstansi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('info instansi widget provides view data for authenticated user', function () {
    $user = User::factory()->create([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'instansi' => 'PT Contoh Jaya',
        'role' => 'member',
    ]);

    $this->actingAs($user);

    $widget = app(InfoInstansi::class);

    $method = new ReflectionMethod($widget, 'getViewData');
    $method->setAccessible(true);

    $viewData = $method->invoke($widget);

    expect($viewData['instansi'])->toBe('PT Contoh Jaya');
    expect($viewData['role'])->toBe('MEMBER');
    expect($viewData['nama'])->toBe('Budi Santoso');
    expect($viewData['email'])->toBe('budi@example.com');
});
