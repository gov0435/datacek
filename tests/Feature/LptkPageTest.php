<?php

use App\Filament\App\Pages\Lptk as AppLptkPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('ppg')) {
        Schema::create('ppg', function ($table) {
            $table->unsignedBigInteger('ptk_id')->primary();
        });
    }
});

test('admin can access lptk page in admin panel', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/lptk')
        ->assertSuccessful()
        ->assertSee('Pencarian LPTK')
        ->assertSee('Universitas Negeri Gorontalo');
});

test('member can access lptk page in app panel', function () {
    $member = User::factory()->create(['role' => 'member']);

    $this->actingAs($member)
        ->get('/app/lptk')
        ->assertSuccessful()
        ->assertSee('Pencarian LPTK')
        ->assertSee('Universitas Negeri Gorontalo');
});

test('guest cannot access admin lptk page', function () {
    $this->get('/admin/lptk')
        ->assertRedirect('/admin/login');
});

test('guest cannot access app lptk page', function () {
    $this->get('/app/lptk')
        ->assertRedirect('/app/login');
});

test('lptk page can filter results by search query', function () {
    $member = User::factory()->create(['role' => 'member']);

    Livewire::actingAs($member)
        ->test(AppLptkPage::class)
        ->assertSee('Universitas Negeri Gorontalo')
        ->assertSee('Universitas Negeri Makassar')
        ->set('search', 'Gorontalo')
        ->assertSee('Universitas Negeri Gorontalo')
        ->assertDontSee('Universitas Negeri Makassar');
});
