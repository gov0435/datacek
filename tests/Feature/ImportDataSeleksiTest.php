<?php

use App\Models\PotensiPpg;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('ppg')) {
        Schema::create('ppg', function ($table) {
            $table->bigInteger('ptk_id')->unique()->nullable();
            $table->string('nama', 255)->nullable();
            $table->string('status_sekolah', 50)->nullable();
            $table->string('sta_jenjang', 50)->nullable();
            $table->string('sta_asn', 50)->nullable();
            $table->string('keterangan_jabatan', 50)->nullable();
            $table->string('sta_sekolah', 50)->nullable();
            $table->string('sta_ijazah', 50)->nullable();
            $table->string('sta_kandidat', 50)->nullable();
            $table->integer('tahun')->nullable();
            $table->float('gelombang')->nullable();
            $table->string('periode', 50)->nullable();
            $table->float('nuptk')->nullable();
            $table->bigInteger('nik')->nullable();
            $table->float('nip')->nullable();
            $table->string('status_validasi_nik', 50)->nullable();
            $table->text('ptk_foto')->nullable();
            $table->string('no_telp', 128)->nullable();
            $table->string('email_belajar_id', 50)->nullable();
            $table->string('status_tautan_belajar', 50)->nullable();
            $table->string('alamat_surel', 50)->nullable();
            $table->string('alamat_provinsi', 50)->nullable();
            $table->string('alamat_kota', 50)->nullable();
            $table->string('pegawai', 50)->nullable();
            $table->string('jenis_ptk', 50)->nullable();
            $table->string('npsn', 50)->nullable();
            $table->string('naungan', 50)->nullable();
            $table->string('jenis_sekolah', 50)->nullable();
            $table->string('jenjang', 50)->nullable();
            $table->string('kota', 50)->nullable();
            $table->string('status_verval_ijazah', 50)->nullable();
            $table->string('jenis_verval_ijazah', 50)->nullable();
            $table->string('bidang_studi_ppg', 50)->nullable();
            $table->string('waktu_mulai_daftar', 50)->nullable();
            $table->string('status_ajuan', 50)->nullable();
            $table->string('status_biodata', 50)->nullable();
            $table->string('status_bidang_studi_ppg', 50)->nullable();
            $table->string('layak_daftar', 50)->nullable();
            $table->string('keberminatan_status', 50)->nullable();
            $table->string('status_daftar', 50)->nullable();
            $table->boolean('is_check')->default(false)->nullable();
            $table->boolean('is_serdik')->default(false);
            $table->string('statusppg', 255)->nullable();
        });
    }
});

function createTestCsv(string $content): string
{
    $path = base_path('repos/data/data_baru.csv');
    file_put_contents($path, $content);

    return $path;
}

afterEach(function () {
    $path = base_path('repos/data/data_baru.csv');
    if (file_exists($path)) {
        unlink($path);
    }
});

it('inserts new records from CSV', function () {
    createTestCsv("Ptk ID,Nama,Status Sekolah,Jenjang,Status Daftar\n1001,Test User 1,Negeri,SD,Belum Daftar\n");

    $this->artisan('app:import-data-seleksi')->assertSuccessful();

    $this->assertDatabaseHas('ppg', [
        'ptk_id' => 1001,
        'nama' => 'Test User 1',
        'status_sekolah' => 'Negeri',
        'jenjang' => 'SD',
        'status_daftar' => 'Belum Daftar',
    ]);
});

it('updates only specific columns for existing records', function () {
    PotensiPpg::query()->insert([
        'ptk_id' => 1002,
        'nama' => 'Original Name',
        'status_sekolah' => 'Original Status',
        'status_ajuan' => 'Belum Ajukan',
        'layak_daftar' => 'Tidak',
        'keberminatan_status' => 'Belum Isi',
        'status_daftar' => 'Belum Daftar',
    ]);

    createTestCsv("Ptk ID,Nama,Status Sekolah,Status Ajuan,Layak Daftar,Keberminatan Status,Status Daftar\n1002,New Name,New Status,Sudah Ajukan,Ya,Sudah Isi,Sudah Daftar\n");

    $this->artisan('app:import-data-seleksi')->assertSuccessful();

    $record = PotensiPpg::firstWhere('ptk_id', 1002);

    expect($record->status_ajuan)->toBe('Sudah Ajukan')
        ->and($record->layak_daftar)->toBe('Ya')
        ->and($record->keberminatan_status)->toBe('Sudah Isi')
        ->and($record->status_daftar->value)->toBe('Sudah Daftar')
        ->and($record->nama)->toBe('Original Name')
        ->and($record->status_sekolah)->toBe('Original Status');
});

it('skips rows without ptk_id', function () {
    createTestCsv("Ptk ID,Nama\n,No ID User\n");

    $this->artisan('app:import-data-seleksi')->assertSuccessful();

    expect(PotensiPpg::count())->toBe(0);
});

it('handles dry-run mode without modifying database', function () {
    createTestCsv("Ptk ID,Nama\n1003,Dry Run User\n");

    $this->artisan('app:import-data-seleksi', ['--dry-run' => true])->assertSuccessful();

    expect(PotensiPpg::firstWhere('ptk_id', 1003))->toBeNull();
});

it('deduplicates rows by ptk_id keeping last occurrence', function () {
    createTestCsv("Ptk ID,Nama,Status Daftar\n1005,First,Belum Daftar\n1005,Second,Sudah Daftar\n");

    $this->artisan('app:import-data-seleksi')->assertSuccessful();

    $record = PotensiPpg::firstWhere('ptk_id', 1005);

    expect($record->nama)->toBe('Second')
        ->and($record->status_daftar->value)->toBe('Sudah Daftar');
});
