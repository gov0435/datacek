<?php

use App\Enums\JenisDokumenDinas;
use App\Filament\App\Resources\DokumenDinas\DokumenDinasResource;
use App\Filament\App\Resources\Sptjm\SptjmResource;
use App\Filament\Resources\SptjmSekolahs\SptjmSekolahResource;
use App\Helpers\FileHelper;
use App\Models\DokumenDinas;
use App\Models\SptjmSekolah;
use App\Models\SptjmUnggahan;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password')->nullable();
        $table->string('role')->default('member');
        $table->string('instansi')->nullable();
        $table->string('kabkota')->nullable();
        $table->string('provider')->nullable();
        $table->string('provider_id')->nullable();
        $table->string('avatar')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('whitelists', function (Blueprint $table): void {
        $table->id();
        $table->string('email')->unique();
        $table->string('nama');
        $table->string('instansi');
        $table->string('kabkota');
        $table->string('role')->default('member');
        $table->timestamps();
    });

    Schema::create('sptjm_sekolah', function (Blueprint $table): void {
        $table->id();
        $table->string('sekolah_npsn')->unique();
        $table->string('sekolah_nama')->nullable();
        $table->string('sekolah_jenjang')->nullable();
        $table->string('sekolah_kota')->nullable();
        $table->string('sekolah_propinsi')->nullable();
        $table->string('scope')->nullable();
        $table->integer('jumlah_guru')->default(0);
        $table->boolean('is_valid')->default(false);
        $table->unsignedBigInteger('generated_by')->nullable();
        $table->timestamps();
    });

    Schema::create('sptjm_unggahan', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('sptjm_sekolah_id');
        $table->string('disk')->default('s3');
        $table->string('file_path');
        $table->string('file_name');
        $table->string('file_mime')->nullable();
        $table->bigInteger('file_size')->nullable();
        $table->text('catatan')->nullable();
        $table->unsignedBigInteger('uploaded_by')->nullable();
        $table->timestamps();
    });

    Schema::create('dokumen_dinas', function (Blueprint $table): void {
        $table->id();
        $table->string('kabkota');
        $table->string('jenis');
        $table->string('disk')->default('s3');
        $table->string('file_path');
        $table->string('file_name');
        $table->string('file_mime')->nullable();
        $table->bigInteger('file_size')->nullable();
        $table->boolean('is_valid')->default(true);
        $table->text('catatan')->nullable();
        $table->unsignedBigInteger('uploaded_by')->nullable();
        $table->timestamps();
    });
});

// ============================================================================
// Enum
// ============================================================================
test('jenis dokumen dinas enum has correct values', function () {
    expect(JenisDokumenDinas::cases())->toHaveCount(2)
        ->and(JenisDokumenDinas::BeritaAcara->value)->toBe('berita_acara')
        ->and(JenisDokumenDinas::DokumenLain->value)->toBe('dokumen_lain');
});

// ============================================================================
// Models
// ============================================================================
test('sptjm sekolah model config', function () {
    $model = new SptjmSekolah;

    expect($model->getTable())->toBe('sptjm_sekolah')
        ->and($model->getFillable())->toContain('sekolah_npsn')
        ->and($model->getFillable())->toContain('jumlah_guru')
        ->and($model->getFillable())->toContain('generated_by')
        ->and($model->getCasts()['is_valid'])->toBe('boolean');
});

test('sptjm sekolah has unggahan relation', function () {
    $model = new SptjmSekolah;

    expect($model->unggahan())->toBeInstanceOf(HasMany::class)
        ->and($model->unggahanValid())->toBeInstanceOf(HasOne::class)
        ->and($model->generatedBy())->toBeInstanceOf(BelongsTo::class);
});

test('sptjm unggahan model config', function () {
    $model = new SptjmUnggahan;

    expect($model->getTable())->toBe('sptjm_unggahan')
        ->and($model->getCasts()['file_size'])->toBe('integer');
});

test('sptjm unggahan belongs to sptjm sekolah', function () {
    $model = new SptjmUnggahan;

    expect($model->sptjmSekolah())->toBeInstanceOf(BelongsTo::class)
        ->and($model->uploadedBy())->toBeInstanceOf(BelongsTo::class);
});

test('dokumen dinas model config', function () {
    $model = new DokumenDinas;

    expect($model->getTable())->toBe('dokumen_dinas')
        ->and($model->getFillable())->toContain('kabkota')
        ->and($model->getFillable())->toContain('jenis')
        ->and($model->getFillable())->toContain('file_path')
        ->and($model->getCasts()['is_valid'])->toBe('boolean')
        ->and($model->getCasts()['jenis'])->toBe('App\Enums\JenisDokumenDinas');
});

test('dokumen dinas has uploadedBy relation', function () {
    $model = new DokumenDinas;

    expect($model->uploadedBy())->toBeInstanceOf(BelongsTo::class);
});

// ============================================================================
// User Role Helpers
// ============================================================================
test('user isKgtk helper returns true for kgtk role', function () {
    $user = new User(['role' => 'kgtk']);

    expect($user->isKgtk())->toBeTrue()
        ->and($user->isMember())->toBeFalse();
});

test('user isMember helper returns true for member role', function () {
    $user = new User(['role' => 'member']);

    expect($user->isKgtk())->toBeFalse()
        ->and($user->isMember())->toBeTrue();
});

test('user isMember helper returns true for null role', function () {
    $user = new User(['role' => null]);

    expect($user->isMember())->toBeTrue();
});

test('user admin role returns false for both kgtk and member', function () {
    $user = new User(['role' => 'admin']);

    expect($user->isKgtk())->toBeFalse()
        ->and($user->isMember())->toBeFalse();
});

// ============================================================================
// Resources
// ============================================================================
test('admin sptjm sekolah resource is list only', function () {
    expect(SptjmSekolahResource::getModel())->toBe(SptjmSekolah::class)
        ->and(array_keys(SptjmSekolahResource::getPages()))->toBe(['index']);
});

test('app sptjm resource is list only', function () {
    expect(SptjmResource::getModel())->toBe(SptjmSekolah::class)
        ->and(array_keys(SptjmResource::getPages()))->toBe(['index']);
});

test('app dokumen dinas resource is list only', function () {
    expect(DokumenDinasResource::getModel())->toBe(DokumenDinas::class)
        ->and(array_keys(DokumenDinasResource::getPages()))->toBe(['index']);
});

// ============================================================================
// FileHelper
// ============================================================================
test('file helper trim file name trims correctly', function () {
    expect(FileHelper::trimFileName(null))->toBeNull()
        ->and(FileHelper::trimFileName(''))->toBeNull()
        ->and(FileHelper::trimFileName('short.pdf'))->toBe('short.pdf')
        ->and(FileHelper::trimFileName('1234567890.pdf'))->toBe('1234567890.pdf')
        ->and(FileHelper::trimFileName('12345678901.pdf'))->toBe('📄....2345678901.pdf')
        ->and(FileHelper::trimFileName('1704067200-document.pdf'))->toBe('📄....0-document.pdf');
});

// ============================================================================
// New Toggle and Relation Tests
// ============================================================================
test('dokumen dinas resource getEloquentQuery retrieves all uploads for user kabkota', function () {
    $user = User::factory()->create([
        'email' => 'kgtk@example.com',
        'role' => 'kgtk',
    ]);

    Whitelist::query()->create([
        'email' => 'kgtk@example.com',
        'nama' => 'Kgtk User',
        'instansi' => 'Dinas Pendidikan',
        'kabkota' => 'Kab. Boalemo',
    ]);

    $doc1 = DokumenDinas::create([
        'kabkota' => 'Kab. Boalemo',
        'jenis' => JenisDokumenDinas::BeritaAcara,
        'disk' => 's3',
        'file_path' => 'ppg/dokumen-dinas/file1.pdf',
        'file_name' => 'file1.pdf',
        'is_valid' => false,
    ]);

    $doc2 = DokumenDinas::create([
        'kabkota' => 'Kab. Boalemo',
        'jenis' => JenisDokumenDinas::BeritaAcara,
        'disk' => 's3',
        'file_path' => 'ppg/dokumen-dinas/file2.pdf',
        'file_name' => 'file2.pdf',
        'is_valid' => true,
    ]);

    $doc3 = DokumenDinas::create([
        'kabkota' => 'Kab. Boalemo',
        'jenis' => JenisDokumenDinas::DokumenLain,
        'disk' => 's3',
        'file_path' => 'ppg/dokumen-dinas/file3.pdf',
        'file_name' => 'file3.pdf',
        'is_valid' => false,
    ]);

    $doc4 = DokumenDinas::create([
        'kabkota' => 'Kota Gorontalo',
        'jenis' => JenisDokumenDinas::BeritaAcara,
        'disk' => 's3',
        'file_path' => 'ppg/dokumen-dinas/file4.pdf',
        'file_name' => 'file4.pdf',
        'is_valid' => true,
    ]);

    $this->actingAs($user);

    $results = DokumenDinasResource::getEloquentQuery()->get();

    expect($results)->toHaveCount(3)
        ->and($results->pluck('id')->all())->toContain($doc1->id)
        ->and($results->pluck('id')->all())->toContain($doc2->id)
        ->and($results->pluck('id')->all())->toContain($doc3->id)
        ->and($results->pluck('id')->all())->not->toContain($doc4->id);
});

test('sptjm sekolah relation latest upload and validation toggle', function () {
    $sekolah = SptjmSekolah::create([
        'sekolah_npsn' => '12345678',
        'sekolah_nama' => 'SD Negeri 1',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => false,
    ]);

    $unggahan1 = SptjmUnggahan::create([
        'sptjm_sekolah_id' => $sekolah->id,
        'file_path' => 'ppg/sptjm/file1.pdf',
        'file_name' => 'file1.pdf',
    ]);

    $unggahan2 = SptjmUnggahan::create([
        'sptjm_sekolah_id' => $sekolah->id,
        'file_path' => 'ppg/sptjm/file2.pdf',
        'file_name' => 'file2.pdf',
    ]);

    expect($sekolah->unggahanValid->id)->toBe($unggahan2->id)
        ->and($sekolah->is_valid)->toBeFalse();

    $sekolah->update(['is_valid' => true]);
    $sekolah->refresh();

    expect($sekolah->is_valid)->toBeTrue()
        ->and($sekolah->unggahanValid->id)->toBe($unggahan2->id);
});
