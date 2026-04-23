# Plan: Import Data Seleksi to Neon Database

This plan outlines the steps to import data from `repos/data/raw_data_seleksi_2026-04-23T06_30_23.817551625+07_00.csv` into the `ppg` table in the Neon database.

## Objectives

- Import data from CSV without overwriting existing records completely.
- For existing records (matched by `ptk_id`):
    - Update only: `status_ajuan`, `layak_daftar`, `keberminatan_status`, and `status_daftar`.
- For new records:
    - Insert all columns that map to the `ppg` table.
- Ensure data consistency by respecting Enums defined in the application.
- Skip CSV columns that have no corresponding DB column.

## Important Context

### Database Schema Notes
- Table `ppg` has **no primary key** at DB level; `ptk_id` is a non-unique index.
- The model `PotensiPpg` declares `ptk_id` as `$primaryKey` but the DB does not enforce uniqueness — use `firstWhere('ptk_id', $value)` instead of `find()`.
- All columns are nullable except `is_serdik`.

### Model Notes
- `$fillable` currently only has `['is_serdik', 'statusppg']` — must use `Model::unguard()` within the command scope to allow mass assignment for all columns.
- Casts: `jenjang` → `Jenjang`, `status_daftar` → `StatusDaftar`, `statusppg` → `StatusPPG`.

### Enum Values
- `Jenjang`: `PAUD`, `SD`, `SLB`, `SMA`, `SMK`, `SMP`, `Lainnya`
- `StatusDaftar`: `Belum Daftar`, `Sudah Daftar`
- `StatusPPG`: `belum_s1`, `bukan_guru`, `meninggal`, `sudah_serdik`, `belum_serdik`

## Data Mapping

### Columns to Import (CSV → DB)

| CSV Header | DB Column | Insert New? | Update Existing? |
|---|---|---|---|
| Ptk ID | ptk_id | Yes (match key) | No |
| Nama | nama | Yes | No |
| Status Sekolah | status_sekolah | Yes | No |
| Sta Jenjang | sta_jenjang | Yes | No |
| Sta Asn | sta_asn | Yes | No |
| Keterangan Jabatan | keterangan_jabatan | Yes | No |
| Sta Sekolah | sta_sekolah | Yes | No |
| Sta Ijazah | sta_ijazah | Yes | No |
| Sta Kandidat | sta_kandidat | Yes | No |
| Tahun | tahun | Yes | No |
| Gelombang | gelombang | Yes | No |
| Periode | periode | Yes | No |
| Nuptk | nuptk | Yes | No |
| Nik | nik | Yes | No |
| Nip | nip | Yes | No |
| Status Validasi Nik | status_validasi_nik | Yes | No |
| Ptk Foto | ptk_foto | Yes | No |
| No Telp | no_telp | Yes | No |
| Email Belajar ID | email_belajar_id | Yes | No |
| Status Tautan Belajar | status_tautan_belajar | Yes | No |
| Alamat Surel | alamat_surel | Yes | No |
| Alamat Provinsi | alamat_provinsi | Yes | No |
| Alamat Kota | alamat_kota | Yes | No |
| Pegawai | pegawai | Yes | No |
| Jenis Ptk | jenis_ptk | Yes | No |
| Npsn | npsn | Yes | No |
| Naungan | naungan | Yes | No |
| Jenis Sekolah | jenis_sekolah | Yes | No |
| Jenjang | jenjang | Yes | No |
| Kota | kota | Yes | No |
| Status Verval Ijazah | status_verval_ijazah | Yes | No |
| Jenis Verval Ijazah | jenis_verval_ijazah | Yes | No |
| Bidang Studi Ppg | bidang_studi_ppg | Yes | No |
| Waktu Mulai Daftar | waktu_mulai_daftar | Yes | No |
| Status Ajuan | status_ajuan | Yes | **Yes** |
| Status Biodata | status_biodata | Yes | No |
| Status Bidang Studi Ppg | status_bidang_studi_ppg | Yes | No |
| Layak Daftar | layak_daftar | Yes | **Yes** |
| Keberminatan Status | keberminatan_status | Yes | **Yes** |
| Status Daftar | status_daftar | Yes | **Yes** |

### Columns to IGNORE (present in CSV but NOT in DB)

These CSV columns have no corresponding `ppg` table column and will be skipped:
- `Dapodik Ptk ID`, `Staus Kirim Simpkb`, `Ppgdj Mhs ID`
- `Tempat Lahir`, `Tanggal Lahir`, `Jenis Kelamin`
- `Agama`, `Kristen Advent`, `Ketunaan`
- `Ibu Nama`, `Ibu Nik`, `Ibu Pekerjaan`, `Ibu Pendidikan`, `Ibu Penghasilan`, `Ibu Tanggal Lahir`
- `Ayah Nama`, `Ayah Nik`, `Ayah Pekerjaan`, `Ayah Pendidikan`, `Ayah Penghasilan`, `Ayah Tanggal Lahir`
- `Kontak Nama`, `Kontak Telp`, `Kontak Hubungan`
- `Alamat Rumah`, `Kode Pos`, `Usia`
- `Bentuk Pendidikan`, `Nama Sekolah`, `Alamat Sekolah`
- `Kelurahan`, `Kecamatan`, `Kota Kode Dapodik`, `Provinsi Kode Dapodik`, `Provinsi`
- `Status Daerah`, `Perguruan Tinggi`, `Kualifikasi Pendidikan`, `Prodi S1`, `Kode Bidang Studi`
- `Waktu Ajuan`, `Keberminatan Alasan`, `Keberminatan Waktu`
- `Keberminatan Tahun Ppg`, `Keberminatan No Serdik`, `Keberminatan Nim`

## Implementation Strategy

### 1. Create Import Command

```bash
php artisan make:command ImportDataSeleksi --no-interaction
```

Command signature: `app:import-data-seleksi {file} {--dry-run}`

### 2. Implementation Logic

```php
<?php

namespace App\Console\Commands;

use App\Models\PotensiPpg;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;

class ImportDataSeleksi extends Command
{
    protected $signature = 'app:import-data-seleksi {file} {--dry-run}';
    protected $description = 'Import data seleksi from CSV into ppg table';

    // CSV Header → DB Column mapping
    private const COLUMN_MAP = [
        'Ptk ID' => 'ptk_id',
        'Nama' => 'nama',
        'Status Sekolah' => 'status_sekolah',
        'Sta Jenjang' => 'sta_jenjang',
        'Sta Asn' => 'sta_asn',
        'Keterangan Jabatan' => 'keterangan_jabatan',
        'Sta Sekolah' => 'sta_sekolah',
        'Sta Ijazah' => 'sta_ijazah',
        'Sta Kandidat' => 'sta_kandidat',
        'Tahun' => 'tahun',
        'Gelombang' => 'gelombang',
        'Periode' => 'periode',
        'Nuptk' => 'nuptk',
        'Nik' => 'nik',
        'Nip' => 'nip',
        'Status Validasi Nik' => 'status_validasi_nik',
        'Ptk Foto' => 'ptk_foto',
        'No Telp' => 'no_telp',
        'Email Belajar ID' => 'email_belajar_id',
        'Status Tautan Belajar' => 'status_tautan_belajar',
        'Alamat Surel' => 'alamat_surel',
        'Alamat Provinsi' => 'alamat_provinsi',
        'Alamat Kota' => 'alamat_kota',
        'Pegawai' => 'pegawai',
        'Jenis Ptk' => 'jenis_ptk',
        'Npsn' => 'npsn',
        'Naungan' => 'naungan',
        'Jenis Sekolah' => 'jenis_sekolah',
        'Jenjang' => 'jenjang',
        'Kota' => 'kota',
        'Status Verval Ijazah' => 'status_verval_ijazah',
        'Jenis Verval Ijazah' => 'jenis_verval_ijazah',
        'Bidang Studi Ppg' => 'bidang_studi_ppg',
        'Waktu Mulai Daftar' => 'waktu_mulai_daftar',
        'Status Ajuan' => 'status_ajuan',
        'Status Biodata' => 'status_biodata',
        'Status Bidang Studi Ppg' => 'status_bidang_studi_ppg',
        'Layak Daftar' => 'layak_daftar',
        'Keberminatan Status' => 'keberminatan_status',
        'Status Daftar' => 'status_daftar',
    ];

    // Columns to update when record already exists
    private const UPDATE_COLUMNS = [
        'status_ajuan',
        'layak_daftar',
        'keberminatan_status',
        'status_daftar',
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $dryRun = $this->option('dry-run');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        Model::unguard();

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        $this->info($dryRun ? '[DRY RUN] Simulating import...' : 'Starting import...');

        $bar = $this->output->createProgressBar();
        $bar->start();

        while ($row = fgetcsv($file)) {
            $csvData = array_combine($headers, $row);
            $mapped = $this->mapRow($csvData);
            $ptkId = $mapped['ptk_id'] ?? null;

            if (! $ptkId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Clean empty strings to null
            $mapped = array_map(fn ($v) => $v === '' ? null : $v, $mapped);

            // Cast numeric fields
            $mapped = $this->castNumericFields($mapped);

            $existing = PotensiPpg::firstWhere('ptk_id', $ptkId);

            if ($existing) {
                $updateData = array_intersect_key($mapped, array_flip(self::UPDATE_COLUMNS));
                if (! $dryRun) {
                    $existing->update($updateData);
                }
                $updated++;
            } else {
                if (! $dryRun) {
                    PotensiPpg::create($mapped);
                }
                $inserted++;
            }

            $bar->advance();
        }

        fclose($file);
        $bar->finish();
        $this->newLine(2);

        Model::reguard();

        $prefix = $dryRun ? '[DRY RUN] Would have: ' : '';
        $this->info("{$prefix}Inserted: {$inserted}");
        $this->info("{$prefix}Updated: {$updated}");
        $this->info("Skipped (no ptk_id): {$skipped}");

        return self::SUCCESS;
    }

    private function mapRow(array $csvData): array
    {
        $mapped = [];
        foreach (self::COLUMN_MAP as $csvHeader => $dbColumn) {
            if (array_key_exists($csvHeader, $csvData)) {
                $mapped[$dbColumn] = $csvData[$csvHeader];
            }
        }
        return $mapped;
    }

    private function castNumericFields(array $data): array
    {
        $intFields = ['ptk_id', 'tahun', 'nik'];
        $floatFields = ['gelombang', 'nuptk', 'nip'];

        foreach ($intFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }
        foreach ($floatFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (float) $data[$field];
            }
        }
        return $data;
    }
}
```

### 3. Handling Enums

The model casts `jenjang` → `Jenjang`, `status_daftar` → `StatusDaftar`, `statusppg` → `StatusPPG` automatically. CSV values must match enum backing values:

| Enum | Valid Values |
|------|-------------|
| `Jenjang` | `PAUD`, `SD`, `SLB`, `SMA`, `SMK`, `SMP`, `Lainnya` |
| `StatusDaftar` | `Belum Daftar`, `Sudah Daftar` |
| `StatusPPG` | Not present in CSV — no action needed |

CSV data already uses matching values (e.g., `PAUD`, `SMP`, `Belum Daftar`). If a value doesn't match, the raw string is stored since the DB column is `varchar(50)` — it won't break.

### 4. Model Preparation

**No changes to `$fillable` needed** — the command uses `Model::unguard()` / `Model::reguard()` to temporarily bypass mass-assignment protection.

### 5. Running the Command

```bash
# Dry run first
php artisan app:import-data-seleksi --dry-run

# Actual import
php artisan app:import-data-seleksi
```

## Verification Plan

### Automated Tests

Create a Pest feature test: `tests/Feature/ImportDataSeleksiTest.php`

```php
use App\Models\PotensiPpg;

it('inserts new records from CSV', function () {
    // Create a temp CSV with test data
    // Run command
    // Assert record exists in DB with correct values
});

it('updates only specific columns for existing records', function () {
    // Create existing PotensiPpg record
    // Create CSV with same ptk_id but different values
    // Run command
    // Assert only status_ajuan, layak_daftar, keberminatan_status, status_daftar changed
    // Assert other columns remain unchanged
});

it('skips rows without ptk_id', function () {
    // Create CSV with empty ptk_id row
    // Run command
    // Assert no record inserted
});

it('handles dry-run mode without modifying database', function () {
    // Run command with --dry-run
    // Assert no records inserted/updated
});
```

### Manual Verification

```bash
# Before import: check current count
php artisan tinker --execute 'echo App\Models\PotensiPpg::count();'

# After import: compare count
php artisan tinker --execute 'echo App\Models\PotensiPpg::count();'

# Spot-check a specific record
php artisan tinker --execute 'echo App\Models\PotensiPpg::firstWhere("ptk_id", 202100346664)?->toJson();'
```
