# Plan: Import `merged_3_dataset.csv` ke Neon

Rencana ini meniru pola [`app/Console/Commands/ImportDataSeleksi.php`](../../app/Console/Commands/ImportDataSeleksi.php)
untuk mengimpor `repos/py/merged_3_dataset.csv` ke tabel baru di Neon PostgreSQL,
mulai dari **script CREATE TABLE**, **command import**, sampai **verifikasi**.

---

## 1. Analisa Sumber Data

File: `repos/py/merged_3_dataset.csv` (dihasilkan oleh
[`repos/py/merge_3_dataset.py`](../py/merge_3_dataset.py)).

| Properti | Nilai |
|---|---|
| Jumlah baris data | **1.672** |
| Jumlah kolom CSV | **78** (+ 1 kolom manual `keterangan` di DB, total 79) |
| Encoding | **UTF-8 with BOM** (`utf-8-sig`) → header pertama mengandung BOM `\uFEFF` |
| Kunci unik | `ptk_id` (sudah di-dedup di Python `dict`, jadi unik) |
| Sumber gabungan | `potensi_keberminatan.json` + `peserta_berminat.json` + `verval_profil.json` |

### Karakteristik penting (hasil profiling)

- **Header sudah `snake_case`** dan cocok 1:1 dengan kalkulasi nama kolom DB →
  mapping nyaris identik (beda dengan `ImportDataSeleksi` yang harus memetakan "Title Case").
- **BOM** pada header pertama (`\uFEFFptk_id`) **wajib dibersihkan**, kalau tidak
  kolom `ptk_id` tidak akan ketemu saat mapping.
- **Identifier dengan leading zero** → HARUS disimpan sebagai `TEXT`, bukan numeric,
  agar angka `0` di depan tidak hilang:
  `nuptk` (`0455759661210042`), `no_hp` (`081225560847`), `verval_no_hp`,
  `verval_nip`, `verval_nuptk`, `verval_rekening_nomor`, `sekolah_npsn`, `verval_sekolah_npsn`.
- **Kolom boolean campur format**: ada yang `0/1` (`has_potensi`, `verval_is_lapor`)
  dan ada yang `True/False` (`is_guru_dapodik`, `verval_is_kasek`). PostgreSQL `boolean`
  menerima keduanya (case-insensitive), tapi command tetap menormalisasi agar aman.
- **Timestamp dua format**: ISO `2026-04-02T17:19:36.000000Z` dan
  `2026-04-03 00:19:36`. `timestamptz` PostgreSQL bisa parse keduanya.
- **3 kolom 100% kosong** (semua 1.672 null): `peserta_keberminatan_alasan`,
  `verval_kandidat_skor_total_final`, `verval_kandidat_status_seleksi` → tetap dibuat
  kolomnya (nullable) untuk data masa depan.
- `ptk_id`, `no_ukg`, `*_id`, `*_nik` panjang 12–16 digit → butuh `BIGINT`
  (melebihi `INT` 32-bit). `nik` di-`TEXT`-kan saja karena identifier.

### Pemetaan tipe kolom (ringkas)

| Kelompok | Kolom | Tipe Neon |
|---|---|---|
| Kunci | `ptk_id` | `BIGINT` PRIMARY KEY |
| Flag sumber | `has_potensi`, `has_peserta`, `has_verval` | `BOOLEAN` |
| Identitas | `nama`, `sekolah_*`, `no_ukg` | `TEXT` |
| Identifier leading-zero | `nuptk`, `no_hp`, `*_npsn`, `verval_nip`, `verval_nik`, `peserta_nik`, `verval_nuptk`, `verval_no_hp`, `verval_rekening_nomor` | `TEXT` |
| ID numerik | `potensi_*_id`, `peserta_id`, `peserta_ppgdj_mhs_id`, `verval_ppgdj_mhs_id`, `potensi_instansi_id`, `potensi_akun_id` | `BIGINT` |
| Boolean verval | `is_guru_dapodik`, `verval_is_*`, `verval_kandidat_is_lulus` | `BOOLEAN` |
| Skor | `verval_kandidat_skor_total_final` | `NUMERIC` |
| Tanggal | `verval_tgl_lahir`, `verval_tmt_guru` | `DATE` |
| Timestamp | `potensi_waktu`, `*_created_at`, `*_updated_at`, `peserta_keberminatan_waktu`, `verval_wkt_ajuan`, `verval_wkt_verval` | `TIMESTAMPTZ` |
| Teks panjang | `peserta_bagren_error`, `potensi_alasan`, `verval_perti_ppg` | `TEXT` |
| Sisanya | status/keterangan pendek | `TEXT` |

> Memakai `TEXT` untuk semua kolom string menghindari error truncation
> (`peserta_bagren_error` sampai 230 char). PostgreSQL `TEXT` tanpa penalti performa.

---

## 2. Script Neon — CREATE TABLE

Nama tabel: **`survey_ppg`** (membedakan dari tabel `ppg` yang sudah ada).
Jalankan di Neon SQL Editor / `psql`:

```sql
DROP TABLE IF EXISTS survey_ppg;

CREATE TABLE survey_ppg (
    -- Kunci & flag sumber
    ptk_id                            BIGINT PRIMARY KEY,
    has_potensi                       BOOLEAN,
    has_peserta                       BOOLEAN,
    has_verval                        BOOLEAN,

    -- Identitas (deduplicated: verval -> peserta -> potensi)
    nama                              TEXT,
    nuptk                             TEXT,
    no_ukg                            TEXT,
    no_hp                             TEXT,
    is_guru_dapodik                   BOOLEAN,
    sekolah_nama                      TEXT,
    sekolah_npsn                      TEXT,
    sekolah_jenjang                   TEXT,
    sekolah_kota                      TEXT,
    sekolah_propinsi                  TEXT,

    -- Potensi keberminatan
    potensi_ppgdj_keberminatan_id     BIGINT,
    potensi_status                    TEXT,
    potensi_alasan                    TEXT,
    potensi_waktu                     TIMESTAMPTZ,
    potensi_instansi_id               BIGINT,
    potensi_akun_id                   BIGINT,
    potensi_created_at                TIMESTAMPTZ,
    potensi_updated_at                TIMESTAMPTZ,

    -- Peserta berminat
    peserta_id                        BIGINT,
    peserta_ppgdj_mhs_id              BIGINT,
    peserta_nik                       TEXT,
    peserta_nik_status                TEXT,
    peserta_pusdatin_status           TEXT,
    peserta_bagren_status             TEXT,
    peserta_bagren_error              TEXT,
    peserta_layak_daftar              TEXT,
    peserta_keberminatan_status       TEXT,
    peserta_keberminatan_waktu        TIMESTAMPTZ,
    peserta_keberminatan_alasan       TEXT,
    peserta_created_at                TIMESTAMPTZ,
    peserta_updated_at                TIMESTAMPTZ,

    -- Verval profil
    verval_ppgdj_mhs_id               BIGINT,
    verval_status                     TEXT,
    verval_wkt_ajuan                  TIMESTAMPTZ,
    verval_wkt_verval                 TIMESTAMPTZ,
    verval_nama                       TEXT,
    verval_nik                        TEXT,
    verval_nuptk                      TEXT,
    verval_kelamin                    TEXT,
    verval_tmp_lahir                  TEXT,
    verval_tgl_lahir                  DATE,
    verval_no_hp                      TEXT,
    verval_email_belajar              TEXT,
    verval_status_kepegawaian         TEXT,
    verval_nip                        TEXT,
    verval_jabatan                    TEXT,
    verval_tmt_guru                   DATE,
    verval_kualifikasi                TEXT,
    verval_prodi_s1                   TEXT,
    verval_sekolah_nama               TEXT,
    verval_sekolah_npsn               TEXT,
    verval_kecamatan                  TEXT,
    verval_jenjang                    TEXT,
    verval_kota                       TEXT,
    verval_propinsi                   TEXT,
    verval_jurusan_ppg                TEXT,
    verval_perti_ppg                  TEXT,
    verval_prodi_ppg                  TEXT,
    verval_mapel_ppg                  TEXT,
    verval_is_lapor                   BOOLEAN,
    verval_is_undur                   BOOLEAN,
    verval_is_peserta                 BOOLEAN,
    verval_is_cadangan                BOOLEAN,
    verval_is_plpg                    BOOLEAN,
    verval_is_kasek                   BOOLEAN,
    verval_is_lengkap_pks             BOOLEAN,
    verval_is_lengkap_laporan         BOOLEAN,
    verval_is_epks                    BOOLEAN,
    verval_kandidat_skor_total_final  NUMERIC,
    verval_kandidat_is_lulus          BOOLEAN,
    verval_kandidat_status_seleksi    TEXT,
    verval_rekening_nama              TEXT,
    verval_rekening_nomor             TEXT,
    verval_rekening_cabang            TEXT,

    -- Kolom manual (di luar 78 kolom CSV) — diisi user, TIDAK pernah ditimpa import
    keterangan                        TEXT
);

-- Index bantu untuk query umum
CREATE INDEX idx_survey_ppg_has_flags    ON survey_ppg (has_potensi, has_peserta, has_verval);
CREATE INDEX idx_survey_ppg_nik          ON survey_ppg (peserta_nik);
CREATE INDEX idx_survey_ppg_layak_daftar ON survey_ppg (peserta_layak_daftar);
```

> **Alternatif (cara Laravel):** buat migration
> `php artisan make:migration create_survey_ppg_table` dengan kolom yang sama,
> lalu `php artisan migrate`. Pilih salah satu — jangan dobel. Untuk konsistensi
> dengan tabel `ppg` (yang dibuat manual di Neon, tanpa migration), script SQL di
> atas adalah jalur utama yang diminta.

---

## 3. Model Eloquent

Buat `app/Models/SurveyPpg.php` meniru
[`app/Models/PotensiPpg.php`](../../app/Models/PotensiPpg.php):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyPpg extends Model
{
    protected $table = 'survey_ppg';

    protected $primaryKey = 'ptk_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ptk_id' => 'integer',
            'has_potensi' => 'boolean',
            'has_peserta' => 'boolean',
            'has_verval' => 'boolean',
            'is_guru_dapodik' => 'boolean',
            'verval_is_lapor' => 'boolean',
            'verval_is_undur' => 'boolean',
            'verval_is_peserta' => 'boolean',
            'verval_is_cadangan' => 'boolean',
            'verval_is_plpg' => 'boolean',
            'verval_is_kasek' => 'boolean',
            'verval_is_lengkap_pks' => 'boolean',
            'verval_is_lengkap_laporan' => 'boolean',
            'verval_is_epks' => 'boolean',
            'verval_kandidat_is_lulus' => 'boolean',
            'verval_kandidat_skor_total_final' => 'float',
        ];
    }
}
```

---

## 4. Command Import

Buat command meniru pola `ImportDataSeleksi` (parse → bersihkan → cast → `upsert`
per-chunk), tapi karena ini **import penuh** (bukan update sebagian kolom),
`UPDATE_COLUMNS` = seluruh kolom selain `ptk_id`.

```bash
php artisan make:command ImportMergedDataset --no-interaction
```

Signature: `app:import-merged-dataset {--dry-run}`
Konstanta `FILE_PATH = 'repos/py/merged_3_dataset.csv'`.

### Logika inti (perbedaan dari `ImportDataSeleksi`)

| Aspek | `ImportDataSeleksi` (lama) | `ImportMergedDataset` (baru) |
|---|---|---|
| Mapping header | Title Case → snake_case (manual) | Header sudah snake_case → bersihkan BOM, pakai langsung |
| Kolom di-update saat upsert | hanya 4 kolom | semua kolom CSV (refresh penuh), **kecuali `keterangan`** |
| Boolean | tidak ada | normalisasi `True/False/1/0/""` → `bool`/`null` |
| Numeric cast | `ptk_id`, `tahun`, `nik`, dll | `ptk_id` & `*_id` → int, skor → float, identifier tetap string |
| Key upsert | `ptk_id` | `ptk_id` |

Poin implementasi:

1. **Bersihkan BOM** pada header: `$headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);`
2. **Empty string → null**: `array_map(fn ($v) => $v === '' ? null : $v, $row)`.
3. **Normalisasi boolean**: `True/1 → true`, `False/0 → false`, `null → null`.
4. **Cast numeric**: `ptk_id` dan kolom `*_id` ke `int`; `verval_kandidat_skor_total_final` ke `float`.
   Identifier leading-zero (`nuptk`, `no_hp`, `nip`, dll) **dibiarkan string**.
5. **Dedup by `ptk_id`** dengan `$rows[$ptkId] = $mapped` (sama seperti command lama).
6. **`keterangan` TIDAK dimasukkan** ke array data (tidak ada di CSV) — biarkan DB
   simpan nilai existing. Karena `upsert` hanya menulis kolom yang ada di payload,
   `keterangan` aman dari INSERT (jadi `NULL` untuk row baru) maupun UPDATE.
7. **Upsert per chunk 250** dengan `SurveyPpg::upsert($chunk, ['ptk_id'], $updateColumns)`,
   di mana `$updateColumns` = **semua kolom CSV kecuali `ptk_id` dan `keterangan`**.
   Dengan begitu menjalankan ulang `app:import-merged-dataset` me-refresh data CSV
   tapi **tidak pernah menimpa `keterangan`** yang diisi manual.

### Menjalankan

```bash
# Simulasi dulu
php artisan app:import-merged-dataset --dry-run

# Import sungguhan
php artisan app:import-merged-dataset
```

---

## 5. Rencana Verifikasi

### Test otomatis (Pest)

`tests/Feature/ImportMergedDatasetTest.php`:

- `it('inserts new rows from CSV')` — buat CSV temp, jalankan, assert row ada + tipe benar.
- `it('upserts existing ptk_id without duplicating')` — jalankan 2x, assert count tetap.
- `it('preserves leading zeros for nuptk/no_hp')` — assert `nuptk` masih `0455...`.
- `it('normalizes booleans True/False/0/1')` — assert kolom boolean ter-cast benar.
- `it('preserves keterangan on re-import')` — isi `keterangan` manual, jalankan import
  lagi dengan data berbeda, assert `keterangan` **tetap** dan kolom CSV ter-update.
- `it('does not write in --dry-run mode')`.

```bash
php artisan test --compact --filter=ImportMergedDataset
```

### Verifikasi manual

```bash
# Hitung jumlah (harus 1672)
php artisan tinker --execute 'echo App\Models\SurveyPpg::count();'

# Spot check leading zero
php artisan tinker --execute 'echo App\Models\SurveyPpg::find(201500073659)?->nuptk;'
```

```sql
-- Di Neon
SELECT count(*) FROM survey_ppg;                       -- 1672
SELECT count(*) FROM survey_ppg WHERE has_verval;      -- yang punya verval
SELECT ptk_id, nuptk, no_hp FROM survey_ppg LIMIT 5;   -- cek leading zero
```

---

## 6. Urutan Eksekusi

```diagram
╭──────────────────────────╮
│ 1. Jalankan CREATE TABLE │  (Neon SQL editor)
│    survey_ppg           │
╰────────────┬─────────────╯
             ▼
╭──────────────────────────╮
│ 2. Buat Model SurveyPpg │
╰────────────┬─────────────╯
             ▼
╭──────────────────────────╮
│ 3. Buat Command          │
│    ImportMergedDataset   │
╰────────────┬─────────────╯
             ▼
╭──────────────────────────╮
│ 4. Tulis + run Pest test │
╰────────────┬─────────────╯
             ▼
╭──────────────────────────╮
│ 5. --dry-run lalu import │
╰────────────┬─────────────╯
             ▼
╭──────────────────────────╮
│ 6. Verifikasi count/data │
╰──────────────────────────╯
```

---

## Catatan & Keputusan Terbuka

- **Nama tabel**: `survey_ppg` (sudah ditetapkan).
- **Kolom `keterangan`**: kolom manual di luar 78 kolom CSV, diisi user, dan
  **tidak pernah ditimpa** saat `app:import-merged-dataset` dijalankan ulang
  (di-exclude dari `$updateColumns` upsert).
- **Relasi ke `ppg`**: `survey_ppg.ptk_id` bisa dijadikan FK ke `ppg.ptk_id`,
  tapi tabel `ppg` tidak punya unique constraint yang konsisten → **disarankan
  tanpa FK** dulu, cukup index.
- **Refresh data**: import ulang me-refresh semua kolom CSV dari file terbaru,
  tapi `keterangan` tetap utuh.
- Migration vs raw SQL: pilih satu jalur agar tidak konflik.
```