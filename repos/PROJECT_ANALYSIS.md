# Project Analysis: Verval KGTK (PPG26)

> **Tanggal Analisis:** 2026-05-03  
> **Versi Framework:** Laravel 13 — Filament 5.5.2 — PHP 8.4 — PostgreSQL (Neon Serverless)  
> **Target Deploy:** Docker / Coolify

---

## Ringkasan

Verval KGTK adalah portal verifikasi & validasi data guru peserta PPG (Pendidikan Profesi Guru) 2026 untuk wilayah Provinsi Gorontalo. Aplikasi ini digunakan oleh operator Dinas Pendidikan kabupaten/kota untuk memverifikasi status kelayakan guru, melakukan verval inline, dan melihat dashboard statistik regional. Otentikasi menggunakan Google OAuth dengan sistem whitelist email. Arsitektur multi-panel Filament memisahkan portal Dinas (panel `app`) dan portal Admin (panel `admin`) dengan data scoping berbasis KabKota/Provinsi.

---

## Arsitektur

| Layer | Teknologi / Pola |
|-------|------------------|
| Framework | Laravel 13 (PHP ^8.3) |
| Admin Panel | Filament 5.5.2 (multi-panel: `app` + `admin`) |
| Auth | Google OAuth via Socialite 5.26 + whitelist email |
| Database | PostgreSQL (Neon serverless, production) / SQLite (dev lokal) |
| Frontend | Blade + Filament SPA mode + Vite + Tailwind CSS 4 |
| Queue/Session/Cache | Database driver |
| Testing | Pest PHP 4.6 (9 feature tests, 2 unit tests) |
| Infrastruktur | Docker (serversideup/php:8.4-fpm-nginx) via Coolify |

### Pola Arsitektur

- **Multi-panel Filament** — 2 panel terpisah dengan auth, resource, dan widget berbeda
- **Region-scoped multi-tenancy** — Data guru difilter otomatis berdasarkan KabKota pengguna
- **Whitelist-gated authentication** — Email wajib terdaftar di tabel `whitelists` sebelum login
- **ETL via upsert** — Import CSV massal dengan upsert chunked (250 baris per batch)
- **Enum-driven domain** — 5 PHP Enum (Jenjang, KabKota, LayakDaftar, StatusDaftar, StatusPPG) dengan helper bisnis

### Dua Panel

| Panel | Path | Pengguna | Scope Data |
|-------|------|----------|------------|
| `app` | `/app` | Operator Dinas (role: member) | Terbatas ke KabKota/provinsi masing-masing |
| `admin` | `/admin` | Super Admin (role: admin) | Seluruh data, whitelist, session monitoring |

### Alur Auth

```
User → /app → Google OAuth → SocialAuthController →
  1. Cek email di tabel whitelists
  2. Jika tidak ada → redirect /no-auth (akses ditolak)
  3. Jika sudah ada User → login (tanpa update profile)
  4. Jika whitelist tapi belum User → buat User baru (role=member, instansi & kabkota dari whitelist)
  5. Login → redirect ke /app dashboard
```

### Data Scoping

- **KabKota scope:** PAUD, SD, SMP — difilter berdasarkan `kota` user
- **Provinsi scope:** SLB, SMA, SMK — tanpa filter `kota` (lintas kabupaten)
- Diterapkan di `DataPotensiResource::getEloquentQuery()` dan `RegistrationStatsWidget`

### Model Utama

| Model | Tabel | Key | Keterangan |
|-------|-------|-----|------------|
| `User` | `users` | - | Auth user (FilamentUser), kolom: instansi, kabkota, role, provider |
| `PotensiPpg` | `ppg` | `ptk_id` (unique) | Data guru PPG — source eksternal, 40+ kolom |
| `Whitelist` | `whitelists` | `email` (unique) | Daftar email yang diizinkan login |
| `SessionUser` | `sessions` | - | Session monitoring (Laravel default) |

---

## Analisis Keamanan

### **KRITIKAL — Credentials Terekspos di `.env`**

File `.env` saat ini mengandung:

```
GOOGLE_CLIENT_ID="120068676181-5ik2u7jgikm50a4be9ukd1tathuirssg.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="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"
GOOGLE_REDIRECT_URI="https://fwd.host/http://ppg26.test/auth/google/callback"
DB_URL=postgresql://neondb_owner:npg_FsNT7ygfv2EH@ep-noisy-feather-a1qyz5s0.ap-southeast-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require
```

**Resiko:**  
- Google OAuth client secret terekspos langsung
- Database connection string (Neon PostgreSQL) dengan password kredibel  
- Jika file `.env` masuk ke repository publik atau tersebar, seluruh akses aplikasi bocor

**Rekomendasi segera:**  
1. Rotate Google Client Secret di [Google Cloud Console](https://console.cloud.google.com/) **SEKARANG**
2. Rotate password database Neon di dashboard Neon **SEKARANG**
3. Tambahkan `.env` ke `.gitignore` (pastikan sudah)
4. Gunakan secret manager atau environment variables server-side untuk production

### **MEDIUM — Session Security**

- `SESSION_ENCRYPT=false` — session data tidak dienkripsi
- `SESSION_DRIVER=database` — session disimpan plaintext di database
- Tidak ada rate limiting pada endpoint auth

### **MEDIUM — Debug Mode di Production**

- `APP_DEBUG=true` — harus `false` di production. Jika true, stack trace internal Laravel akan terekspos ke user saat error, membocorkan struktur direktori, query, dan potensi data sensitif

### **LOW — Socialite Provider Tidak Divalidasi Ketat**

- Route `/auth/{provider}/callback` menerima parameter `provider` arbitrer
- Validasi hanya mengecek terhadap `allowed_drivers` di config, tapi ada potensi enumeration attack

### **LOW — CSRF pada CSV Export**

- Export CSV endpoint tidak memiliki proteksi CSRF eksplisit (bergantung pada Filament middleware)

---

## Analisis Performa

### Index Database

✅ **Sudah baik:**
- Index di `ppg.ptk_id` (unique), `ppg.jenjang`, `ppg.kota`, `ppg.nama`
- Query scoping menggunakan `WHERE IN` pada kolom ber-index

### Potensi Masalah

⚠️ **N+1 Query pada Whitelist Lookup:**

Di `DataPotensiResource`, method `getAuthenticatedWhitelist()` dipanggil setiap kali resource diakses. Meskipun menggunakan `once()` helper per request, ini tetap menjalankan query setiap request baru.

```php
// app/Filament/App/Resources/DataPotensis/DataPotensiResource.php:145
private static function getAuthenticatedWhitelist(): ?Whitelist
{
    return once(function (): ?Whitelist {
        // query ke whitelists setiap request
    });
}
```

⚠️ **Widget Stats Menjalankan 3 Query Terpisah:**

`RegistrationStatsWidget::getStats()` melakukan 3x `count()` pada query yang sama:

```php
$totalCount = $query->count();
$layakCount = (clone $query)->where('layak_daftar', 'Layak Daftar')->count();
$sudahDaftarCount = (clone $query)->where('status_daftar', ...)->count();
```

Ini bisa dioptimasi menjadi 1 query aggregate.

⚠️ **Dashboard Widgets Query Per Panel:**

Setiap panel (`admin` + `app`) memiliki widget `RegistrationStatsWidget` yang masing-masing menjalankan query terpisah — total 4 widget di 2 panel.

⚠️ **Tidak Ada Caching:**

Tidak ada implementasi Redis/Memcached untuk cache query yang sering diakses seperti data statistik dashboard atau dropdown filter.

### Rekomendasi Performa

- Gunakan single query aggregate untuk widget stats dashboard
- Tambahkan cache (Redis atau file cache) untuk data dashboard dengan TTL 5-15 menit
- Pertimbangkan materialized view jika data sering dibaca tapi jarang berubah

---

## Analisis Kode

### Kekuatan

✅ **Solid Foundation:**
- Laravel 13 + Filament 5.5 mengikuti best practice konvensi framework
- Struktur direktori bersih dengan pemisahan jelas antara panel `app` dan `admin`
- Enum-driven domain logic dengan helper bisnis (`StatusPPG::isNotEligible()`)
- Test coverage cukup untuk auth flow dan import CSV

✅ **Pattern yang Baik:**
- `DataPotensisTable` sebagai class terpisah (bukan inline di Resource)
- `select()` + `cursor()` untuk CSV export menghindari memory exhaustion
- Chunked upsert (250 rows) pada import CSV
- `once()` helper untuk menghindari duplicate query dalam satu request
- SPA mode pada panel `app` untuk navigasi lebih cepat

✅ **Error Handling:**
- SocialAuthController menangani exception dengan baik, redirect ke no-auth
- Import CSV menangani file not found, missing headers, column mismatch

### Masalah

#### Duplikasi Logika Scoping

Logika `isProvinsiScope()`, `getAuthenticatedWhitelistKabKota()`, `extractKabKotaValue()`, dan `getAuthenticatedWhitelist()` **terduplikasi** di dua tempat:

1. `DataPotensiResource` (app/Filament/App/Resources/DataPotensis/DataPotensiResource.php)
2. `RegistrationStatsWidget` (app/Filament/App/Widgets/RegistrationStatsWidget.php)

Kedua class memiliki implementasi yang hampir identik. Ini melanggar DRY principle — setiap perubahan pada logika scoping harus diupdate di dua tempat.

#### Konstanta Duplikat

`JENJANG_KAB_KOTA` dan `JENJANG_PROVINSI` didefinisikan ulang di:
- `DataPotensiResource` (baris 21-22)
- `RegistrationStatsWidget` (baris 17-18)

#### Konstanta Tidak Digunakan

`DataPotensisTable` meng-import beberapa konstanta enum yang tidak digunakan secara eksplisit (tergantung pada Filament magic). Ini tidak bug, tapi kurang bersih.

#### Duplikasi Migration

Migration `0001_01_01_000000_create_users_table.php` memiliki kolom yang di-override oleh migration selanjutnya (`add_socialite_columns_to_users_table.php`, `add_kabkota_to_users_and_whitelists_tables.php`). Ini wajar untuk project yang berkembang, tapi migration awal bisa disesuaikan jika project masih baru.

#### Field `instansi` Tidak Tercast

Di model `User`, `instansi` adalah string biasa tanpa cast, sementara di `Whitelist` field terkait tidak dicast juga. Tidak ada enum Instansi — tapi konsistensi casting antara model User dan Whitelist akan membantu.

---

## Saran Perbaikan

### Prioritas 1 — Keamanan (SEGERA)

1. **Rotate semua credentials yang terekspos** — Google Client Secret & password database Neon
2. **Set `APP_DEBUG=false`** di production
3. **Set `SESSION_ENCRYPT=true`** untuk mengenkripsi session data di database
4. **Tambahkan rate limiting** pada endpoint auth callback (`/auth/{provider}/callback`)
5. **Gunakan `.env.example`** tanpa nilai real untuk dokumentasi — pastikan `.env` sudah di `.gitignore`

### Prioritas 2 — Refactoring (High Impact, Low Risk)

6. **Ekstrak scope logic ke dedicated class**

   Buat class `App\Services\RegionScope` yang menangani:
   - `isProvinsiScope()`
   - `getAllowedJenjang()`
   - `getKabKota()`
   - `getWhitelist()`

   Gunakan di `DataPotensiResource` dan `RegistrationStatsWidget` via dependency injection atau Facade.

7. **Gunakan shared constants**

   Pindahkan `JENJANG_KAB_KOTA` dan `JENJANG_PROVINSI` ke satu tempat (misal: `RegionScope` service atau config file).

8. **Optimasi Widget Stats Query**

   ```php
   // Ganti 3x count dengan 1x aggregate query
   $stats = PotensiPpg::query()
       ->whereIn('jenjang', $allowedJenjang)
       ->when($kabKota, fn ($q) => $q->where('kota', $kabKota))
       ->selectRaw("
           COUNT(*) as total,
           SUM(CASE WHEN layak_daftar = 'Layak Daftar' THEN 1 ELSE 0 END) as layak,
           SUM(CASE WHEN status_daftar = ? THEN 1 ELSE 0 END) as sudah_daftar
       ", [StatusDaftar::SudahDaftar->value])
       ->first();
   ```

### Prioritas 3 — Fitur & Infrastruktur

9. **Implementasi cache untuk dashboard**

   Cache hasil query dashboard dengan TTL 5-15 menit:

   ```php
   $stats = Cache::remember('dashboard:stats:'.$kabKota, now()->addMinutes(10), fn () => ...);
   ```

   Pertimbangkan `cache:clear` atau forget cache di command import.

10. **Tambahkan Redis untuk production**

    Redis untuk cache dan session akan signifikan mempercepat aplikasi dibanding database driver. Session di database menambah I/O pada setiap request.

11. **Tambahkan logging & monitoring**

    - Log setiap percobaan login gagal (email tidak terdaftar)
    - Log setiap perubahan `statusppg` untuk audit trail
    - Implementasi health check endpoint yang lebih informatif

12. **Pisahkan config environment**

    Buat file config terpisah untuk `local`, `staging`, `production` atau gunakan Laravel environment detection yang lebih robust:

    ```php
    // config/app.php
    'env' => env('APP_ENV', 'production'),
    ```

13. **Tambahkan `app:import-data-seleksi` ke scheduler**

    Jika CSV diupdate berkala, tambahkan ke `app/Console/Kernel.php`:

    ```php
    $schedule->command('app:import-data-seleksi')->hourly();
    ```

14. **Validasi CSV lebih ketat**

    Di `ImportDataSeleksi`, tambahkan validasi header CSV sebelum processing:
    - Cek apakah semua required columns ada
    - Report jumlah column mismatch
    - Log row yang diskip dengan alasan spesifik

15. **Tambah unit test untuk scope logic**

    Saat ini belum ada test untuk `DataPotensiResource` data scoping logic atau `RegistrationStatsWidget` filtering.

16. **Pertimbangkan Filament Resource Testing**

    Tambahkan test untuk Filament table interaksi (filter, sort, inline edit statusppg) menggunakan `livewire()` helper:

    ```php
    use function Pest\Livewire\livewire;

    livewire(ListDataPotensis::class)
        ->assertCanSeeTableRecords($potensiPpgs)
        ->filterTable('jenjang', 'SD')
        ->assertCanSeeTableRecords($sdRecords);
    ```

### Prioritas 4 — UX & Polish

17. **Custom favicon & branding**

    Aplikasi saat ini menggunakan default Filament favicon. Tambahkan logo KGTK sebagai favicon.

18. **Loading state pada export**

    Export CSV bisa memakan waktu jika data besar. Tambahkan loading indicator atau notifikasi "Export sedang diproses".

19. **Pagination size aware**

    Data Potensi bisa ribuan baris — pastikan pagination size optimal (saat ini menggunakan default Filament).

20. **Responsive table untuk mobile**

    Operator Dinas mungkin mengakses dari mobile — pastikan tabel data potensi responsif.

---

## Daftar File Penting

| File | Peran |
|------|-------|
| `app/Providers/Filament/AppPanelProvider.php` | Konfigurasi panel Dinas (`/app`) |
| `app/Providers/Filament/AdminPanelProvider.php` | Konfigurasi panel Admin (`/admin`) |
| `app/Http/Controllers/Auth/SocialAuthController.php` | Google OAuth handler + whitelist check |
| `app/Filament/App/Resources/DataPotensis/DataPotensiResource.php` | Resource utama + data scoping logic |
| `app/Filament/App/Resources/DataPotensis/Tables/DataPotensisTable.php` | Tabel data guru dengan verval inline |
| `app/Filament/App/Widgets/RegistrationStatsWidget.php` | Widget statistik region-scoped |
| `app/Filament/Widgets/RegistrationStatsWidget.php` | Widget statistik admin (global) |
| `app/Filament/Widgets/StatusPPGByRegionChart.php` | Chart distribusi status PPG per region |
| `app/Console/Commands/ImportDataSeleksi.php` | CSV import command |
| `app/Enums/StatusPPG.php` | Enum status sertifikasi guru |
| `app/Enums/KabKota.php` | Enum wilayah Gorontalo |
| `app/Models/PotensiPpg.php` | Model tabel `ppg` |
| `app/Models/User.php` | Model auth user |
| `app/Models/Whitelist.php` | Model whitelist email |
| `routes/web.php` | Route definitions |
| `config/services.php` | Socialite & Google OAuth config |
| `database/migrations/` | 10 migration files (5 Laravel default + 5 custom) |

---

## Kesimpulan

Verval KGTK adalah aplikasi yang dibangun dengan fondasi solid mengikuti Laravel & Filament best practices. Arsitektur multi-panel dengan region-scoping sudah tepat untuk use case multi-tenant Dinas Pendidikan. Test coverage untuk auth flow dan import CSV cukup baik.

**Isu paling kritis saat ini adalah kredensial yang terekspos di `.env`** — ini harus segera diatasi dengan rotasi secret dan memastikan `.env` tidak akan masuk ke repository publik.

Dari sisi kode, duplikasi scope logic antara Resource dan Widget adalah technical debt yang paling terlihat dan sebaiknya di-refactor. Optimasi query widget dan implementasi caching akan memberikan peningkatan performa yang signifikan saat data guru mencapai puluhan ribu baris.

Prioritas pengerjaan: **Keamanan → Refactoring → Performa → Testing → UX**.
