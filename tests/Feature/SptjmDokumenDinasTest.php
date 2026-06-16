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
test('sptjm sekolah model config', function () {
    $model = new SptjmSekolah;

    expect($model->getTable())->toBe('sptjm_sekolah')
        ->and($model->getFillable())->toContain('sekolah_npsn')
        ->and($model->getFillable())->toContain('jumlah_guru')
        ->and($model->getFillable())->toContain('generated_by');
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
        ->and($model->getCasts()['is_valid'])->toBe('boolean')
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
        ->and(FileHelper::trimFileName('12345678901.pdf'))->toBe('....2345678901.pdf')
        ->and(FileHelper::trimFileName('1704067200-document.pdf'))->toBe('....0-document.pdf');
});
