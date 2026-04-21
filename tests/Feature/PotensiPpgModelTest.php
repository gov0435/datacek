<?php

use App\Models\PotensiPpg;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('ppg', function (Blueprint $table): void {
        $table->unsignedBigInteger('ptk_id')->primary();
        $table->string('nama')->nullable();
        $table->integer('tahun')->nullable();
        $table->bigInteger('nik')->nullable();
        $table->float('gelombang')->nullable();
        $table->float('nuptk')->nullable();
        $table->float('nip')->nullable();
        $table->boolean('is_check')->nullable()->default(false);
        $table->boolean('is_serdik')->default(false);
    });
});

test('potensi ppg model reads records from ppg table', function () {
    DB::table('ppg')->insert([
        'ptk_id' => 120001,
        'nama' => 'Budi Santoso',
        'tahun' => 2026,
        'nik' => 3201001001000001,
        'gelombang' => 1,
        'nuptk' => 1234567890,
        'nip' => 198812122020011001,
        'is_check' => false,
    ]);

    $record = PotensiPpg::query()->first();

    expect($record)->not->toBeNull()
        ->and($record?->getTable())->toBe('ppg')
        ->and($record?->nama)->toBe('Budi Santoso');
});

test('potensi ppg model applies configured casts', function () {
    DB::table('ppg')->insert([
        'ptk_id' => 120002,
        'nama' => 'Siti Aminah',
        'tahun' => 2026,
        'nik' => 3201001001000002,
        'gelombang' => 2,
        'nuptk' => 9876543210,
        'nip' => 198903132020012002,
        'is_check' => true,
        'is_serdik' => true,
    ]);

    $record = PotensiPpg::query()->findOrFail(120002);

    expect($record->ptk_id)->toBeInt()
        ->and($record->tahun)->toBeInt()
        ->and($record->nik)->toBeInt()
        ->and($record->gelombang)->toBeFloat()
        ->and($record->is_check)->toBeBool()
        ->and($record->is_serdik)->toBeBool();
});

test('potensi ppg sets is_serdik default to false', function () {
    DB::table('ppg')->insert([
        'ptk_id' => 120003,
        'nama' => 'Andi Pratama',
    ]);

    $record = PotensiPpg::query()->findOrFail(120003);

    expect($record->is_serdik)->toBeFalse();
});
