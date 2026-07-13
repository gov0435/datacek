<?php

use App\Enums\JenisDokumenDinas;
use App\Filament\App\Resources\DokumenDinas\DokumenDinasResource;
use App\Filament\App\Resources\Sptjm\SptjmResource;
use App\Filament\App\Widgets\SptjmProgressChart;
use App\Filament\App\Widgets\SptjmStatsWidget;
use App\Filament\Resources\SptjmSekolahs\SptjmSekolahResource;
use App\Filament\Widgets\SptjmProgressByRegionChart;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $table->boolean('has_hardcopy')->default(false);
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

test('sptjm sekolah has_hardcopy model config', function () {
    $model = new SptjmSekolah;

    expect($model->getFillable())->toContain('has_hardcopy')
        ->and($model->getCasts()['has_hardcopy'])->toBe('boolean');
});

test('sptjm upload by kgtk sets has_hardcopy to true', function () {
    $user = User::factory()->create([
        'email' => 'kgtk@example.com',
        'role' => 'kgtk',
    ]);

    $sekolah = SptjmSekolah::create([
        'sekolah_npsn' => '12345678',
        'sekolah_nama' => 'SD Negeri 1',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => false,
        'has_hardcopy' => false,
    ]);

    $this->actingAs($user);

    DB::transaction(function () use ($sekolah, $user): void {
        $sekolah->unggahan()->create([
            'disk' => 's3',
            'file_path' => 'ppg/sptjm/file.pdf',
            'file_name' => 'file.pdf',
            'uploaded_by' => $user->id,
        ]);

        if (Auth::user()?->isKgtk()) {
            $sekolah->update(['has_hardcopy' => true]);
        } else {
            $sekolah->update(['has_hardcopy' => false]);
        }
    });

    $sekolah->refresh();
    expect($sekolah->has_hardcopy)->toBeTrue();
});

test('sptjm upload by member sets has_hardcopy to false', function () {
    $user = User::factory()->create([
        'email' => 'member@example.com',
        'role' => 'member',
    ]);

    $sekolah = SptjmSekolah::create([
        'sekolah_npsn' => '12345678',
        'sekolah_nama' => 'SD Negeri 1',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => false,
        'has_hardcopy' => true,
    ]);

    $this->actingAs($user);

    DB::transaction(function () use ($sekolah, $user): void {
        $sekolah->unggahan()->create([
            'disk' => 's3',
            'file_path' => 'ppg/sptjm/file.pdf',
            'file_name' => 'file.pdf',
            'uploaded_by' => $user->id,
        ]);

        if (Auth::user()?->isKgtk()) {
            $sekolah->update(['has_hardcopy' => true]);
        } else {
            $sekolah->update(['has_hardcopy' => false]);
        }
    });

    $sekolah->refresh();
    expect($sekolah->has_hardcopy)->toBeFalse();
});

test('sptjm stats widget calculates and displays correct counts including has_hardcopy', function () {
    $user = User::factory()->create([
        'email' => 'member@example.com',
        'role' => 'member',
    ]);

    Whitelist::create([
        'email' => 'member@example.com',
        'nama' => 'Member User',
        'instansi' => 'Dinas Pendidikan Boalemo',
        'kabkota' => 'Kab. Boalemo',
        'role' => 'member',
    ]);

    $this->actingAs($user);

    // Create some schools in Boalemo (scoped to member's whitelist)
    $sekolah1 = SptjmSekolah::create([
        'sekolah_npsn' => '11111111',
        'sekolah_nama' => 'SD Negeri 1 Boalemo',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => false,
        'has_hardcopy' => false,
        'scope' => 'kabkota',
    ]);

    $sekolah2 = SptjmSekolah::create([
        'sekolah_npsn' => '22222222',
        'sekolah_nama' => 'SD Negeri 2 Boalemo',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => true,
        'has_hardcopy' => true,
        'scope' => 'kabkota',
    ]);

    $sekolah3 = SptjmSekolah::create([
        'sekolah_npsn' => '33333333',
        'sekolah_nama' => 'SD Negeri 3 Boalemo',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => false,
        'has_hardcopy' => true,
        'scope' => 'kabkota',
    ]);

    // Add unggahan for sekolah3 to make it "Proses Validasi"
    $sekolah3->unggahan()->create([
        'disk' => 's3',
        'file_path' => 'ppg/sptjm/file3.pdf',
        'file_name' => 'file3.pdf',
        'uploaded_by' => $user->id,
    ]);

    // Create a school outside Kab. Boalemo (should not be in scoped stats)
    SptjmSekolah::create([
        'sekolah_npsn' => '44444444',
        'sekolah_nama' => 'SD Negeri 4 Gorontalo',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kota Gorontalo',
        'is_valid' => true,
        'has_hardcopy' => true,
        'scope' => 'kabkota',
    ]);

    $widget = app(SptjmStatsWidget::class);

    $method = new ReflectionMethod($widget, 'getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    // Total: 3 (sekolah1, sekolah2, sekolah3 in Boalemo)
    // Belum Upload: 1 (sekolah1 - is_valid false, has no unggahan)
    // Proses Validasi: 1 (sekolah3 - is_valid false, has unggahan)
    // Valid: 1 (sekolah2 - is_valid true)
    // Hardcopy Diterima: 2 (sekolah2, sekolah3 - has_hardcopy true)

    expect($stats)->toHaveCount(5);

    $totalStat = $stats[0];
    expect($totalStat->getLabel())->toBe('Total SPTJM')
        ->and($totalStat->getValue())->toBe(3);

    $belumUploadStat = $stats[1];
    expect($belumUploadStat->getLabel())->toBe('Belum Upload')
        ->and($belumUploadStat->getValue())->toBe(1);

    $prosesValidasiStat = $stats[2];
    expect($prosesValidasiStat->getLabel())->toBe('Proses Validasi')
        ->and($prosesValidasiStat->getValue())->toBe(1);

    $validStat = $stats[3];
    expect($validStat->getLabel())->toBe('Valid')
        ->and($validStat->getValue())->toBe(1);

    $hardcopyStat = $stats[4];
    expect($hardcopyStat->getLabel())->toBe('Hardcopy Diterima')
        ->and($hardcopyStat->getValue())->toBe(2);
});

test('sptjm progress chart calculates and displays correct datasets and labels including has_hardcopy', function () {
    $user = User::factory()->create([
        'email' => 'member2@example.com',
        'role' => 'member',
    ]);

    Whitelist::create([
        'email' => 'member2@example.com',
        'nama' => 'Member User 2',
        'instansi' => 'Dinas Pendidikan Boalemo',
        'kabkota' => 'Kab. Boalemo',
        'role' => 'member',
    ]);

    $this->actingAs($user);

    // Create some schools in Boalemo (scoped to member's whitelist)
    // We will place them in PAUD and SD jenjang to see if the counts match
    $sekolah1 = SptjmSekolah::create([
        'sekolah_npsn' => '11111115',
        'sekolah_nama' => 'SD Negeri 1 Boalemo',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => false,
        'has_hardcopy' => false,
        'scope' => 'kabkota',
    ]);

    $sekolah2 = SptjmSekolah::create([
        'sekolah_npsn' => '22222225',
        'sekolah_nama' => 'SD Negeri 2 Boalemo',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => true,
        'has_hardcopy' => true,
        'scope' => 'kabkota',
    ]);

    $sekolah3 = SptjmSekolah::create([
        'sekolah_npsn' => '33333335',
        'sekolah_nama' => 'PAUD Pembina Boalemo',
        'sekolah_jenjang' => 'PAUD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => false,
        'has_hardcopy' => true,
        'scope' => 'kabkota',
    ]);

    // Add unggahan for sekolah3 to make it "Proses Validasi"
    $sekolah3->unggahan()->create([
        'disk' => 's3',
        'file_path' => 'ppg/sptjm/file3.pdf',
        'file_name' => 'file3.pdf',
        'uploaded_by' => $user->id,
    ]);

    // Create a school outside Kab. Boalemo (should not be in scoped stats)
    SptjmSekolah::create([
        'sekolah_npsn' => '44444445',
        'sekolah_nama' => 'SD Negeri 4 Gorontalo',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kota Gorontalo',
        'is_valid' => true,
        'has_hardcopy' => true,
        'scope' => 'kabkota',
    ]);

    $widget = app(SptjmProgressChart::class);

    $method = new ReflectionMethod($widget, 'getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    // PAUD, SD, SMP, Lainnya
    expect($data['labels'])->toBe(['PAUD', 'SD', 'SMP', 'Lainnya']);
    expect($data['datasets'])->toHaveCount(5);

    // Datasets format:
    // [0] Valid (PAUD: 0, SD: 1, SMP: 0, Lainnya: 0)
    // [1] Proses Validasi (PAUD: 1, SD: 0, SMP: 0, Lainnya: 0)
    // [2] Belum Diupload (PAUD: 0, SD: 1, SMP: 0, Lainnya: 0)
    // [3] Hardcopy Diterima (PAUD: 1, SD: 1, SMP: 0, Lainnya: 0)
    // [4] Hardcopy Belum Diterima (PAUD: 0, SD: 1, SMP: 0, Lainnya: 0)

    expect($data['datasets'][0]['data'])->toBe([0, 1, 0, 0]) // Valid
        ->and($data['datasets'][1]['data'])->toBe([1, 0, 0, 0]) // Proses Validasi
        ->and($data['datasets'][2]['data'])->toBe([0, 1, 0, 0]) // Belum Diupload
        ->and($data['datasets'][3]['data'])->toBe([1, 1, 0, 0]) // Hardcopy Diterima
        ->and($data['datasets'][4]['data'])->toBe([0, 1, 0, 0]); // Hardcopy Belum Diterima
});

test('admin sptjm progress by region chart calculates and displays correct datasets and labels including has_hardcopy', function () {
    // Create schools in different regions
    SptjmSekolah::create([
        'sekolah_npsn' => '99999111',
        'sekolah_nama' => 'SD Boalemo',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Boalemo',
        'is_valid' => true,
        'has_hardcopy' => true,
        'scope' => 'kabkota',
    ]);

    SptjmSekolah::create([
        'sekolah_npsn' => '99999222',
        'sekolah_nama' => 'SD Bonebolango',
        'sekolah_jenjang' => 'SD',
        'sekolah_kota' => 'Kab. Bonebolango',
        'is_valid' => false,
        'has_hardcopy' => false,
        'scope' => 'kabkota',
    ]);

    $widget = app(SptjmProgressByRegionChart::class);

    $method = new ReflectionMethod($widget, 'getData');
    $method->setAccessible(true);
    $data = $method->invoke($widget);

    // Regions are:
    // Kab. Boalemo, Kab. Bonebolango, Kab. Gorontalo, Kab. Gorontalo Utara, Kab. Pohuwato, Kota Gorontalo, Provinsi
    expect($data['labels'])->toHaveCount(7);
    expect($data['datasets'])->toHaveCount(5);

    // Let's check Kab. Boalemo (index 0) and Kab. Bonebolango (index 1)
    // Valid for Boalemo should be 1
    expect($data['datasets'][0]['data'][0])->toBe(1) // Valid for Boalemo
        ->and($data['datasets'][0]['data'][1])->toBe(0) // Valid for Bonebolango
        ->and($data['datasets'][3]['data'][0])->toBe(1) // Hardcopy Diterima for Boalemo
        ->and($data['datasets'][3]['data'][1])->toBe(0) // Hardcopy Diterima for Bonebolango
        ->and($data['datasets'][4]['data'][1])->toBe(1); // Hardcopy Belum Diterima for Bonebolango (1 school total - 0 hardcopy)
});
