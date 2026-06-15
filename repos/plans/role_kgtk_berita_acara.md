# Plan: Role `kgtk` + Berita Acara Sekolah (Generate Admin + Upload Versioned ke S3)

Fitur baru:
1. **Role `kgtk`** pada panel App.
2. **Admin** punya tombol **Generate Data Sekolah** (per Kabupaten/Kota / Provinsi) yang meng-query sekolah dengan guru `potensi_status != Berminat` lalu **upsert** ke `berita_acara_sekolah`. Sekolah yang sudah tidak punya guru non-Berminat (semua Berminat) **dihapus** berikut riwayat uploadnya.
3. **kgtk** meng-upload **Berita Acara per sekolah** ke **S3**, boleh **banyak versi** (riwayat disimpan), dengan **satu flag** penanda file valid terakhir.
4. **kgtk** juga meng-upload **Dokumen Dinas (per dinas/wilayah, bukan per sekolah)** ke **S3** — versioning & flag valid yang sama, mendukung beberapa **jenis** dokumen dinas.

Mengikuti pola scoping di
[`DataKeberminatanResource`](../../app/Filament/App/Resources/DataKeberminatan/DataKeberminatanResource.php)
dan struktur folder Filament yang sudah ada.

---

## 1. Analisa Data (sumber: `survey_ppg`)

Kolom relevan dari [`repos/sql/create_survey_ppg.sql`](../sql/create_survey_ppg.sql):
`ptk_id`, `nama`, `no_hp`, `sekolah_npsn`, `sekolah_nama`, `sekolah_jenjang`,
`sekolah_kota`, `sekolah_propinsi`, `potensi_status`.

### Eligibilitas guru = "bukan Berminat"
Pakai **`potensi_status IS DISTINCT FROM 'Berminat'`** sehingga baris `NULL` (belum survey) **ikut terhitung**.
Ini sejalan dengan alasan user: sebagian kab/kota/provinsi belum mengisi survey (`potensi_status = NULL`),
jadi tombol generate admin dibuat fleksibel & idempotent.

### Pembagian scope (mengikuti `DataKeberminatanResource`)
| Scope | Filter wilayah | Filter jenjang |
|---|---|---|
| **Kab/Kota** | `sekolah_kota = '<nilai KabKota>'` | `PAUD, SD, SMP, Lainnya` |
| **Provinsi** | seluruh provinsi (tanpa filter kota) | `SLB, SMA, SMK` |

`KabKota` enum ([`app/Enums/KabKota.php`](../../app/Enums/KabKota.php)) sudah memuat case `Provinsi`,
jadi **selektor admin = `KabKota` enum** (nilai `Provinsi` → scope provinsi, selain itu → scope kab/kota).

### Hasil profiling (query langsung DB)

Sekolah dengan ≥1 guru non-Berminat per kota:

| `sekolah_kota` | Total sekolah | Sekolah non-Berminat |
|---|---:|---:|
| Kab. Gorontalo | 344 | 152 |
| Kab. Bonebolango | 177 | 49 |
| Kab. Boalemo | 168 | 95 |
| Kab. Pohuwato | 150 | 112 |
| Kab. Gorontalo Utara | 147 | 104 |
| Kota Gorontalo | 128 | 67 |

Jenjang provinsi (`SLB`+`SMA`+`SMK`) = 13+27+67 = **107 baris guru**.
Nilai `sekolah_kota` di DB **persis sama** dengan value enum (`Kab. Gorontalo`, dst).

---

## 2. Arsitektur

```diagram
                 ADMIN PANEL                              APP PANEL (role kgtk)
 ╭──────────────────────────────────╮          ╭──────────────────────────────────╮
 │ BeritaAcaraSekolahResource        │          │ BeritaAcaraResource               │
 │  ── header action:                │          │  ── list sekolah (scope kabkota)  │
 │     "Generate per Kab/Kota/Prov"  │          │  ── aksi Upload BA (S3, versioned)│
 ╰───────────────┬──────────────────╯          ╰───────────────┬──────────────────╯
                 │ query survey_ppg                              │ upsert versi + set is_valid
                 │ (IS DISTINCT FROM 'Berminat')                 │
                 ▼  insert baris BARU saja                       ▼
        ╭────────────────────────────╮  1     N  ╭────────────────────────────────╮
        │ berita_acara_sekolah       │──────────▶│ berita_acara_unggahan          │
        │  (master per npsn)         │           │  (riwayat file, is_valid flag) │
        ╰────────────────────────────╯           ╰───────────────┬────────────────╯
                                                                  ▼
                                                       disk S3: berita-acara/{npsn}/...
```

**Tabel:**
- `berita_acara_sekolah` — **1 baris per sekolah** (master, di-generate admin).
- `berita_acara_unggahan` — **N baris per sekolah** (riwayat unggahan); kolom `is_valid` menandai file valid terakhir. File lama **tidak dihapus**.
- `dokumen_dinas` — **dokumen tingkat dinas** (per wilayah `kabkota`/`provinsi`, bukan per sekolah). N versi per (`kabkota`, `jenis`); kolom `is_valid` menandai file valid terakhir, riwayat disimpan. **Tidak butuh generate** — kgtk upload langsung.

```diagram
                    APP PANEL (role kgtk)
 ╭──────────────────────────────────────────╮
 │ DokumenDinasResource                       │
 │  ── upload per JENIS dokumen (scope dinas) │──▶ dokumen_dinas (versioned, is_valid)
 │  ── tanpa generate admin                   │      └─▶ S3: dokumen-dinas/{kabkota}/{jenis}/...
 ╰──────────────────────────────────────────╯
```

---

## 3. Prasyarat: Storage S3

S3 disk sudah terdaftar di [`config/filesystems.php`](../../config/filesystems.php) **tetapi paket belum terpasang**.
Target: **S3-compatible** (bukan AWS asli) → wajib set `AWS_ENDPOINT` dan umumnya `AWS_USE_PATH_STYLE_ENDPOINT=true`.

1. **Tambah dependency** (butuh approval): `composer require league/flysystem-aws-s3-v3 "^3.0"`.
2. **Tambah env** di `.env` & [`.env.example`](../../.env.example):
   ```
   AWS_ACCESS_KEY_ID=
   AWS_SECRET_ACCESS_KEY=
   AWS_DEFAULT_REGION=          # mis. us-east-1 (sesuai provider)
   AWS_BUCKET=
   AWS_ENDPOINT=               # WAJIB untuk S3-compatible, mis. https://s3.nevacloud.id
   AWS_USE_PATH_STYLE_ENDPOINT=true
   ```
3. Upload pakai disk `s3`, **visibility private**. Unduhan lewat **temporary URL** (`Storage::disk('s3')->temporaryUrl(...)`) via aksi Filament — bukan URL publik.
   - **Error handling**: Jika upload ke S3 gagal, throw error langsung (tanpa fallback/retry).
4. Path object: `berita-acara/{sekolah_npsn}/{uuid}.{ext}`.

---

## 4. Database

### 4.1 `berita_acara_sekolah` (master)
`php artisan make:migration create_berita_acara_sekolah_table --no-interaction`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `sekolah_npsn` | `string` **unique** | kunci per-sekolah |
| `sekolah_nama` | `string nullable` | denormalisasi tampilan |
| `sekolah_jenjang` | `string nullable` | denormalisasi |
| `sekolah_kota` | `string nullable` | scope kab/kota |
| `sekolah_propinsi` | `string nullable` | scope provinsi |
| `scope` | `string nullable` | `kabkota` / `provinsi` (asal generate) |
| `jumlah_guru` | `unsignedInteger` | snapshot jumlah guru non-Berminat saat generate |
| `generated_by` | `foreignId nullable` → `users.id` | admin yang generate |
| `timestamps` | | |

Index: `unique('sekolah_npsn')`, index `sekolah_kota`, `sekolah_propinsi`.

### 4.2 `berita_acara_unggahan` (riwayat file)
`php artisan make:migration create_berita_acara_unggahan_table --no-interaction`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `berita_acara_sekolah_id` | `foreignId` → `berita_acara_sekolah.id` cascade | relasi master |
| `disk` | `string` default `s3` | |
| `file_path` | `string` | object key di S3 |
| `file_name` | `string` | nama asli file |
| `file_mime` | `string nullable` | |
| `file_size` | `unsignedBigInteger nullable` | |
| `is_valid` | `boolean` default `true` | **penanda file valid terakhir** |
| `catatan` | `text nullable` | catatan opsional kgtk |
| `uploaded_by` | `foreignId nullable` → `users.id` | |
| `timestamps` | | |

Index: `index(['berita_acara_sekolah_id', 'is_valid'])`.

> **Aturan flag:** saat upload baru → set semua `is_valid=false` untuk sekolah tsb, lalu baris baru `is_valid=true`. Dibungkus `DB::transaction()`. Baris lama **tidak dihapus** (riwayat).

### 4.3 `dokumen_dinas` (dokumen tingkat dinas, versioned)
`php artisan make:migration create_dokumen_dinas_table --no-interaction`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `kabkota` | `string` | scope dinas (value `KabKota` enum, incl. `Provinsi`) |
| `jenis` | `string` | jenis dokumen dinas (mis. `surat_pengantar`, `rekapitulasi`) |
| `disk` | `string` default `s3` | |
| `file_path` | `string` | object key di S3 |
| `file_name` | `string` | nama asli file |
| `file_mime` | `string nullable` | |
| `file_size` | `unsignedBigInteger nullable` | |
| `is_valid` | `boolean` default `true` | **penanda versi valid terakhir** per (`kabkota`,`jenis`) |
| `catatan` | `text nullable` | |
| `uploaded_by` | `foreignId nullable` → `users.id` | |
| `timestamps` | | |

Index: `index(['kabkota', 'jenis', 'is_valid'])`.

> **Aturan flag (sama seperti BA):** upload baru untuk (`kabkota`,`jenis`) → set `is_valid=false` semua versi lama lalu baris baru `is_valid=true`, dibungkus `DB::transaction()`. Riwayat tidak dihapus.
> **Jenis dokumen** sebaiknya dibuat sebagai **enum** `JenisDokumenDinas` (mengikuti pola `app/Enums/*`) agar pilihan terkontrol & mudah ditambah. *(Daftar jenis final menyusul — lihat §10.)*

### 4.4 `whitelists.role`
`php artisan make:migration add_role_to_whitelists_table --no-interaction` — kolom `role` string default `member`.

### 4.5 Models
- [`BeritaAcaraSekolah`](../../app/Models/BeritaAcaraSekolah.php): `hasMany(BeritaAcaraUnggahan)`, helper `unggahanValid(): HasOne` (`where is_valid=true latestOfMany`), `generatedBy(): BelongsTo`.
- `BeritaAcaraUnggahan`: `belongsTo(BeritaAcaraSekolah)`, `uploadedBy(): BelongsTo`, cast `is_valid => boolean`.
- `DokumenDinas`: `uploadedBy(): BelongsTo`, cast `is_valid => boolean` (+ `jenis => JenisDokumenDinas::class` untuk enum).

### 4.6 Index pada `survey_ppg`
Tambah index pada migration untuk optimasi query guru per sekolah:
```php
$table->index(['sekolah_npsn', 'potensi_status']);
```

---

## 5. Admin: Generate Data Sekolah

Resource baru `app/Filament/Resources/BeritaAcaraSekolahs/` (panel admin, pola folder sama seperti
[`Whitelists`](../../app/Filament/Resources/Whitelists/WhitelistResource.php)).

### 5.1 Tabel
Kolom: `sekolah_npsn`, `sekolah_nama`, `sekolah_jenjang` (badge), `sekolah_kota`, `jumlah_guru`,
`status_ba` (badge dari relasi `unggahanValid`: **Sudah Valid / Belum Upload**), `unggahanValid.updated_at`.
Filter: `sekolah_kota`, `scope`, dan `status_ba` (sudah/belum).

### 5.2 Header Action: `generateSekolah`
```php
Action::make('generateSekolah')
    ->label('Generate Data Sekolah')
    ->schema([
        Select::make('kabkota')
            ->label('Kabupaten/Kota / Provinsi')
            ->options(KabKota::class)
            ->required(),
    ])
    ->action(function (array $data): void {
        $scopeProvinsi = str_contains(strtolower($data['kabkota']), 'provinsi');

        $jenjang = $scopeProvinsi
            ? ['SLB', 'SMA', 'SMK']
            : ['PAUD', 'SD', 'SMP', 'Lainnya'];

        $rows = SurveyPpg::query()
            ->selectRaw('
                sekolah_npsn,
                MAX(sekolah_nama)     as sekolah_nama,
                MAX(sekolah_jenjang)  as sekolah_jenjang,
                MAX(sekolah_kota)     as sekolah_kota,
                MAX(sekolah_propinsi) as sekolah_propinsi,
                COUNT(*) FILTER (WHERE potensi_status IS DISTINCT FROM \'Berminat\') as jumlah_guru
            ')
            ->whereNotNull('sekolah_npsn')
            ->whereIn('sekolah_jenjang', $jenjang)
            ->when(! $scopeProvinsi, fn ($q) => $q->where('sekolah_kota', $data['kabkota']))
            ->groupBy('sekolah_npsn')
            ->havingRaw('COUNT(*) FILTER (WHERE potensi_status IS DISTINCT FROM \'Berminat\') > 0')
            ->get();

        // UPSERT by sekolah_npsn:
        //  - npsn baru  -> insert
        //  - npsn lama  -> REFRESH jumlah_guru + denormalisasi
        $payload = $rows->map(fn ($r) => [
            'sekolah_npsn'     => $r->sekolah_npsn,
            'sekolah_nama'     => $r->sekolah_nama,
            'sekolah_jenjang'  => $r->sekolah_jenjang,
            'sekolah_kota'     => $r->sekolah_kota,
            'sekolah_propinsi' => $r->sekolah_propinsi,
            'scope'            => $scopeProvinsi ? 'provinsi' : 'kabkota',
            'jumlah_guru'      => $r->jumlah_guru,
            'generated_by'     => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ])->all();

        BeritaAcaraSekolah::query()->upsert(
            $payload,
            uniqueBy: ['sekolah_npsn'],
            update: ['sekolah_nama', 'sekolah_jenjang', 'sekolah_kota', 'sekolah_propinsi', 'jumlah_guru', 'updated_at'],
        );

        // HAPUS sekolah yg sudah tidak punya guru non-Berminat (ikut hapus riwayat upload)
        $npsnInResult = $rows->pluck('sekolah_npsn');

        $toDelete = BeritaAcaraSekolah::query()
            ->when($scopeProvinsi, fn ($q) => $q->whereIn('sekolah_jenjang', self::JENJANG_PROVINSI))
            ->unless($scopeProvinsi, fn ($q) => $q
                ->where('sekolah_kota', $kabkotaValue)
                ->whereIn('sekolah_jenjang', self::JENJANG_KAB_KOTA)
            )
            ->whereNotIn('sekolah_npsn', $npsnInResult)
            ->where('jumlah_guru', '>', 0)
            ->pluck('id');

        $deleted = 0;

        if ($toDelete->isNotEmpty()) {
            BeritaAcaraUnggahan::query()
                ->whereIn('berita_acara_sekolah_id', $toDelete)
                ->delete();

            $deleted = BeritaAcaraSekolah::query()
                ->whereIn('id', $toDelete)
                ->delete();
        }

        Notification::make()
            ->title('Generate selesai: '.$rows->count().' sekolah diproses'.($deleted ? ', '.$deleted.' sekolah dihapus (semua guru Berminat)' : ''))
            ->success()->send();
    });
```

**Sifat penting:**
- **npsn baru** → di-insert; **npsn lama** → `jumlah_guru` + kolom denormalisasi **di-refresh**.
- **Sekolah yang sudah tidak punya guru non-Berminat (semua Berminat)** → record sekolah **&** riwayat upload (`berita_acara_unggahan`) **dihapus** dari DB. File di S3 tetap (tidak dihapus).
- `upsert` PostgreSQL butuh **unique constraint** pada `sekolah_npsn` (sudah ada di migrasi §4.1).

> Catatan performa: 6 kab/kota, ≤~350 sekolah/kota → aman dengan satu query agregasi + satu `insert` bulk.

---

## 6. App (kgtk): Upload Berita Acara

Resource `app/Filament/App/Resources/BeritaAcara/` (pola sama `DataKeberminatan`).

### 6.1 Sumber baris & scope
- `getEloquentQuery()` = `BeritaAcaraSekolah::query()->with('unggahanValid')`.
- **Scope kabkota** reuse logika `DataKeberminatanResource` (ambil kabkota dari `Whitelist` user):
  - scope kab/kota → `where('sekolah_kota', $kabKota)`.
  - scope provinsi → tanpa filter kota, batasi `sekolah_jenjang IN (SLB,SMA,SMK)` (atau `scope='provinsi'`).
- kgtk **hanya melihat sekolah yang sudah di-generate admin** untuk wilayahnya.

### 6.2 Kolom
`sekolah_npsn`, `sekolah_nama`, `sekolah_jenjang`, `jumlah_guru`,
`status_ba` (badge 3-state: **Belum Diupload** / **Pending** / **Valid**), `unggahanValid.file_name`, `unggahanValid.updated_at`, `jumlah_versi` (count relasi).

### 6.3 Aksi
- **Detail** (`Action` slideOver): unified panel menampilkan:
  - **Informasi Sekolah** (NPSN, nama, jenjang, kab/kota, jumlah guru) — read-only.
  - **Daftar Guru Non-Berminat** — tabel read-only, dari `survey_ppg` (nama, no HP, status, alasan).
  - **Upload Berita Acara** — form dengan:
    - `FileUpload::make('file')->disk('s3')->visibility('private')->directory("berita-acara/{npsn}")->acceptedFileTypes(['application/pdf'])->maxSize(25000)` (**PDF only, maks 25 MB** = 25.000 KB) + `FileHelper::generateUniqueFileName()`.
    - `Textarea::make('catatan')` opsional.
    - `->action()` dalam `DB::transaction`:
      1. `update` semua unggahan sekolah tsb `is_valid=false`;
      2. `create` unggahan baru `is_valid=true`, isi metadata + `uploaded_by`.
      File lama tetap ada di S3 & DB (riwayat).

> **Aturan valid (final):** **upload terakhir = otomatis valid**. Kolom `is_valid` tetap ada sebagai penanda eksplisit (versi terbaru `true`, sisanya `false`). Tidak ada aksi "tandai valid" manual.

### 6.4 Role gating
- [`User`](../../app/Models/User.php): tambah `isKgtk(): bool`, `isMember(): bool`.
- `BeritaAcaraResource` (app) `canViewAny()`/`shouldRegisterNavigation()` → `Auth::user()?->isKgtk()`.
- **`DataKeberminatanResource` tetap dapat dilihat kgtk** (tidak dibatasi) → kgtk melihat **Data Keberminatan + Berita Acara**.
- [`SocialAuthController::callback()`](../../app/Http/Controllers/Auth/SocialAuthController.php#L40-L70): set `role` dari `whitelist->role` (bukan hardcode `member`); [`WhitelistForm`](../../app/Filament/Resources/Whitelists/Schemas/WhitelistForm.php) tambah `Select role`.

---

## 7. App (kgtk): Dokumen Dinas

Resource `app/Filament/App/Resources/DokumenDinas/` (panel App). Berbeda dari Berita Acara:
**per wilayah dinas (bukan per sekolah)** & **tanpa generate admin**.

### 7.1 Sumber baris & scope
- Baris = **dokumen valid** per wilayah dinas si kgtk.
  - **Query sederhana**: `WHERE kabkota = ? AND is_valid = true` — tampil hanya dokumen yang sudah valid.
  - Upload dokumen baru → via **header action** dengan dropdown enum `jenis`, tidak perlu pre-show semua jenis di tabel.
- **Scope** = `kabkota` dari `Whitelist` user (reuse helper `DataKeberminatanResource`). kgtk hanya melihat/upload dokumen untuk wilayahnya sendiri.

### 7.2 Kolom
`jenis` (label/badge), `status` (**Valid**), `file_name`, `updated_at`, `jumlah_versi`.

### 7.3 Aksi
- **Header Action: Upload Dokumen** (tombol atas tabel):
  - Dropdown pilih `jenis` dari enum `JenisDokumenDinas` (BeritaAcara, DokumenLain).
  - `FileUpload::make('file')->disk('s3')->visibility('private')->directory("ppg/dokumen-dinas/{kabkota}")->acceptedFileTypes(['application/pdf'])->maxSize(25600)` (**PDF, maks 25 MB**) + `Textarea catatan` opsional.
  - `->action()` dalam `DB::transaction`: `update` semua versi (`kabkota`,`jenis`) `is_valid=false` → `create` versi baru `is_valid=true`, isi `uploaded_by`. File lama tetap.
  - Upload **unlimited** — user bisa upload berkali-kali untuk jenis yg sama, semua versi tersimpan.
- **Record Actions**:
  - **Riwayat Versi** (`Action` modal): list versi per (`kabkota`,`jenis`) + unduh per versi.
  - **Unduh Dokumen Valid** (`Action`): `temporaryUrl()` dari S3 untuk versi `is_valid=true`.

### 7.4 Akses & admin
- Visibilitas resource = `Auth::user()?->isKgtk()` (sama seperti Berita Acara).
- (Opsional) Admin bisa diberi resource read-only untuk **memantau dokumen dinas lintas wilayah** — mengikuti pola admin BA. *(Konfirmasi bila diperlukan.)*

---

## 8. Testing (Pest — wajib)

Gunakan `Storage::fake('s3')` + factory. `php artisan make:test --pest ...`.

1. **Migrasi & model**: 4 migrasi jalan (`berita_acara_sekolah`, `berita_acara_unggahan`, `dokumen_dinas`, `whitelists.role`); relasi `BeritaAcaraSekolah ↔ unggahan`, `unggahanValid` benar.
2. **Generate (admin)**:
   - hanya sekolah dengan guru `IS DISTINCT FROM 'Berminat'` yang ter-insert;
   - jenjang sesuai scope (kab/kota vs provinsi);
   - **idempotent (upsert)**: generate dua kali tidak menduplikasi `sekolah_npsn`;
   - **`jumlah_guru` di-refresh** untuk sekolah lama saat re-generate;
   - **sekolah yg semua gurunya sudah Berminat** → record sekolah & riwayat upload dihapus dari DB (file S3 tetap);
   - sekolah yang baru memenuhi syarat (NULL → terisi) ikut masuk saat re-generate.
3. **Scope kgtk**: kgtk kab/kota hanya melihat sekolah di kabkota whitelist-nya; kgtk provinsi melihat sekolah jenjang SLB/SMA/SMK se-provinsi.
4. **Role**: kgtk lihat resource BA **dan** Data Keberminatan; member tidak melihat BA.
5. **SlideOver Detail**:
   - detail panel membuka dengan slideOver (kanan);
   - info sekolah, guru list, dan upload form dalam satu panel;
   - upload file ke `s3` fake tersimpan; `is_valid=true`;
   - upload kedua → versi lama `is_valid=false` & **tetap ada** (count = 2), versi baru `is_valid=true`;
   - `uploaded_by` benar.
6. **Validasi**: non-PDF / over `maxSize` → `assertHasFormErrors`.
7. **Dokumen Dinas**:
   - upload per (`kabkota`,`jenis`) tersimpan ke `s3` fake, `is_valid=true`;
   - upload kedua jenis sama → versi lama `is_valid=false` & tetap ada, baru `is_valid=true`;
   - scope: kgtk hanya melihat/upload dokumen untuk `kabkota`-nya sendiri.

Jalankan: `php artisan test --compact --filter=BeritaAcara` dan `--filter=DokumenDinas`.

---

## 9. Urutan Implementasi

1. (Approval) `composer require league/flysystem-aws-s3-v3`; tambah env S3.
2. Migrasi `berita_acara_sekolah`, `berita_acara_unggahan`, `dokumen_dinas`, `add_role_to_whitelists` + Models + factory + enum `JenisDokumenDinas`.
3. `User` helper role; `SocialAuthController` role dari whitelist; `WhitelistForm` Select role.
4. Admin `BeritaAcaraSekolahResource` + header action `generateSekolah`.
5. App `BeritaAcaraResource` (scope kgtk) + aksi upload/riwayat/unduh + gating.
6. App `DokumenDinasResource` (scope kgtk) + aksi upload/riwayat/unduh.
7. Test Pest; `vendor/bin/pint --dirty --format agent`.

---

## 10. Keputusan Final (terkonfirmasi)

1. **Storage** = **S3-compatible** → set `AWS_ENDPOINT` + `AWS_USE_PATH_STYLE_ENDPOINT=true`; jika upload gagal, **throw error langsung** (tanpa fallback/retry).
2. **kgtk boleh melihat Data Keberminatan** + Berita Acara (tidak ada pembatasan pada `DataKeberminatanResource`).
3. **kgtk ada 2 level**: kab/kota **dan** provinsi — scope mengikuti `Whitelist.kabkota` (nilai `Provinsi` → scope provinsi), reuse logika `DataKeberminatanResource`.
4. **Versi valid** = **upload terakhir otomatis valid**; kolom `is_valid` tetap ada sebagai penanda. Tanpa aksi "tandai valid" manual.
5. **`jumlah_guru` di-refresh** saat re-generate (upsert). Sekolah yang semua gurunya sudah Berminat **dihapus** dari DB (termasuk riwayat upload) — file S3 tetap.
6. **File BA** = **PDF saja**, ukuran maksimal **25 MB** (`acceptedFileTypes(['application/pdf'])`, `maxSize(25600)`).
7. **Dokumen Dinas** = tabel `dokumen_dinas` terpisah, per wilayah (`kabkota`/`provinsi`) & per `jenis`, versioning + flag valid yang sama, PDF maks 25 MB, tanpa generate admin.
8. **Status badge** = **3-state**: `Belum Diupload` | `Pending` | `Valid` (untuk BA dan Dokumen Dinas).
9. **Enum `JenisDokumenDinas`** = 2 nilai: `BeritaAcara`, `DokumenLain`.
10. **Dokumen Dinas UI** = Query sederhana (hanya dokumen valid), **Header Action "Upload Dokumen Dinas"** dengan dropdown enum jenis, unlimited versi per jenis.

### Sisa yang masih perlu nilai konkret (bukan blocker desain)
- Kredensial S3: `AWS_ENDPOINT`, `AWS_REGION`, `AWS_BUCKET`, key/secret.
