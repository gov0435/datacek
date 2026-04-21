<?php

use App\Filament\App\Resources\DataPotensis\DataPotensiResource;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('ppg', function (Blueprint $table): void {
        $table->unsignedBigInteger('ptk_id')->primary();
        $table->string('nama')->nullable();
        $table->string('kota')->nullable();
        $table->string('jenjang')->nullable();
        $table->string('status_daftar')->nullable();
        $table->boolean('is_check')->nullable()->default(false);
        $table->boolean('is_serdik')->default(false);
    });
});

test('resource query only shows ppg data from user whitelist kabkota', function () {
    $user = User::factory()->create([
        'email' => 'member@example.com',
        'kabkota' => 'Kota Gorontalo',
    ]);

    Whitelist::query()->create([
        'email' => 'member@example.com',
        'nama' => 'Member App',
        'instansi' => 'Dinas Pendidikan',
        'kabkota' => 'Kab. Boalemo',
    ]);

    DB::table('ppg')->insert([
        [
            'ptk_id' => 10,
            'nama' => 'Guru Boalemo',
            'kota' => 'Kab. Boalemo',
            'jenjang' => 'SD',
            'status_daftar' => 'Belum Daftar',
            'is_check' => true,
            'is_serdik' => false,
        ],
        [
            'ptk_id' => 11,
            'nama' => 'Guru Kota Gorontalo',
            'kota' => 'Kota Gorontalo',
            'jenjang' => 'SMP',
            'status_daftar' => 'Sudah Daftar',
            'is_check' => false,
            'is_serdik' => true,
        ],
        [
            'ptk_id' => 12,
            'nama' => 'Guru SMA Boalemo',
            'kota' => 'Kab. Boalemo',
            'jenjang' => 'SMA',
            'status_daftar' => 'Belum Daftar',
            'is_check' => false,
            'is_serdik' => false,
        ],
    ]);

    $this->actingAs($user);

    expect(DataPotensiResource::getEloquentQuery()->pluck('ptk_id')->all())
        ->toBe([10]);
});

test('resource query returns empty data when user has no whitelist', function () {
    $user = User::factory()->create([
        'email' => 'without-whitelist@example.com',
        'kabkota' => 'Provinsi',
    ]);

    DB::table('ppg')->insert([
        'ptk_id' => 20,
        'nama' => 'Guru A',
        'kota' => 'Kab. Boalemo',
        'jenjang' => 'SD',
        'status_daftar' => 'Belum Daftar',
        'is_check' => false,
        'is_serdik' => false,
    ]);

    $this->actingAs($user);

    expect(DataPotensiResource::getEloquentQuery()->count())->toBe(0);
});

test('kabkota table filter options follow user whitelist kabkota', function () {
    $user = User::factory()->create([
        'email' => 'filter@example.com',
        'kabkota' => 'Kota Gorontalo',
    ]);

    Whitelist::query()->create([
        'email' => 'filter@example.com',
        'nama' => 'Filter User',
        'instansi' => 'Dinas Pendidikan',
        'kabkota' => 'Kab. Pohuwato',
    ]);

    $this->actingAs($user);

    expect(DataPotensiResource::getKabKotaFilterOptions())
        ->toBe(['Kab. Pohuwato' => 'Kab. Pohuwato']);
});

test('jenjang filter options for kabkota scope are limited to paud sd smp', function () {
    $user = User::factory()->create([
        'email' => 'jenjang-kabkota@example.com',
        'instansi' => 'Dinas Pendidikan Kabupaten',
    ]);

    Whitelist::query()->create([
        'email' => 'jenjang-kabkota@example.com',
        'nama' => 'Jenjang Kabkota',
        'instansi' => 'Dinas Pendidikan Kota',
        'kabkota' => 'Kota Gorontalo',
    ]);

    $this->actingAs($user);

    expect(DataPotensiResource::getJenjangFilterOptions())
        ->toBe([
            'PAUD' => 'PAUD',
            'SD' => 'SD',
            'SMP' => 'SMP',
        ]);
});

test('provinsi scope only shows slb sma smk data and jenjang filter options', function () {
    $user = User::factory()->create([
        'email' => 'provinsi@example.com',
        'instansi' => 'Dinas Pendidikan Provinsi Gorontalo',
    ]);

    Whitelist::query()->create([
        'email' => 'provinsi@example.com',
        'nama' => 'Jenjang Provinsi',
        'instansi' => 'Dinas Pendidikan Provinsi Gorontalo',
        'kabkota' => 'Kab. Boalemo',
    ]);

    DB::table('ppg')->insert([
        [
            'ptk_id' => 30,
            'nama' => 'Guru SLB Boalemo',
            'kota' => 'Kab. Boalemo',
            'jenjang' => 'SLB',
            'status_daftar' => 'Belum Daftar',
            'is_check' => false,
            'is_serdik' => false,
        ],
        [
            'ptk_id' => 31,
            'nama' => 'Guru SD Boalemo',
            'kota' => 'Kab. Boalemo',
            'jenjang' => 'SD',
            'status_daftar' => 'Belum Daftar',
            'is_check' => false,
            'is_serdik' => false,
        ],
        [
            'ptk_id' => 32,
            'nama' => 'Guru SMA Kota Gorontalo',
            'kota' => 'Kota Gorontalo',
            'jenjang' => 'SMA',
            'status_daftar' => 'Belum Daftar',
            'is_check' => false,
            'is_serdik' => false,
        ],
    ]);

    $this->actingAs($user);

    expect(DataPotensiResource::getJenjangFilterOptions())
        ->toBe([
            'SLB' => 'SLB',
            'SMA' => 'SMA',
            'SMK' => 'SMK',
        ]);

    expect(DataPotensiResource::getKabKotaFilterOptions())->toBe([]);

    $ptkIds = DataPotensiResource::getEloquentQuery()->pluck('ptk_id')->all();
    sort($ptkIds);

    expect($ptkIds)->toBe([30, 32]);
});

test('provinsi scope is detected from whitelist kabkota value', function () {
    $user = User::factory()->create([
        'email' => 'provinsi-kabkota@example.com',
        'instansi' => 'Dinas Pendidikan Kota',
    ]);

    Whitelist::query()->create([
        'email' => 'provinsi-kabkota@example.com',
        'nama' => 'Whitelist Provinsi',
        'instansi' => 'Dinas Pendidikan Kota',
        'kabkota' => 'Provinsi',
    ]);

    DB::table('ppg')->insert([
        [
            'ptk_id' => 40,
            'nama' => 'Guru SMK Gorontalo',
            'kota' => 'Kota Gorontalo',
            'jenjang' => 'SMK',
            'status_daftar' => 'Belum Daftar',
            'is_check' => false,
            'is_serdik' => false,
        ],
        [
            'ptk_id' => 41,
            'nama' => 'Guru SD Gorontalo',
            'kota' => 'Kab. Gorontalo',
            'jenjang' => 'SD',
            'status_daftar' => 'Belum Daftar',
            'is_check' => false,
            'is_serdik' => false,
        ],
    ]);

    $this->actingAs($user);

    expect(DataPotensiResource::isProvinsiScope())->toBeTrue();

    expect(DataPotensiResource::getEloquentQuery()->pluck('ptk_id')->all())
        ->toBe([40]);
});
