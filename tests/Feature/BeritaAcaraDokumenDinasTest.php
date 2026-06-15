<?php

use App\Enums\JenisDokumenDinas;
use App\Filament\App\Resources\BeritaAcara\BeritaAcaraResource;
use App\Filament\App\Resources\DokumenDinas\DokumenDinasResource;
use App\Filament\Resources\BeritaAcaraSekolahs\BeritaAcaraSekolahResource;
use App\Models\BeritaAcaraSekolah;
use App\Models\BeritaAcaraUnggahan;
use App\Models\DokumenDinas;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
test('berita acara sekolah model config', function () {
    $model = new BeritaAcaraSekolah;

    expect($model->getTable())->toBe('berita_acara_sekolah')
        ->and($model->getFillable())->toContain('sekolah_npsn')
        ->and($model->getFillable())->toContain('jumlah_guru')
        ->and($model->getFillable())->toContain('generated_by');
});

test('berita acara sekolah has unggahan relation', function () {
    $model = new BeritaAcaraSekolah;

    expect($model->unggahan())->toBeInstanceOf(HasMany::class)
        ->and($model->unggahanValid())->toBeInstanceOf(HasOne::class)
        ->and($model->generatedBy())->toBeInstanceOf(BelongsTo::class);
});

test('berita acara unggahan model config', function () {
    $model = new BeritaAcaraUnggahan;

    expect($model->getTable())->toBe('berita_acara_unggahan')
        ->and($model->getCasts()['is_valid'])->toBe('boolean')
        ->and($model->getCasts()['file_size'])->toBe('integer');
});

test('berita acara unggahan belongs to berita acara sekolah', function () {
    $model = new BeritaAcaraUnggahan;

    expect($model->beritaAcaraSekolah())->toBeInstanceOf(BelongsTo::class)
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
test('admin berita acara sekolah resource is list only', function () {
    expect(BeritaAcaraSekolahResource::getModel())->toBe(BeritaAcaraSekolah::class)
        ->and(array_keys(BeritaAcaraSekolahResource::getPages()))->toBe(['index']);
});

test('app berita acara resource is list only', function () {
    expect(BeritaAcaraResource::getModel())->toBe(BeritaAcaraSekolah::class)
        ->and(array_keys(BeritaAcaraResource::getPages()))->toBe(['index']);
});

test('app dokumen dinas resource is list only', function () {
    expect(DokumenDinasResource::getModel())->toBe(DokumenDinas::class)
        ->and(array_keys(DokumenDinasResource::getPages()))->toBe(['index']);
});
