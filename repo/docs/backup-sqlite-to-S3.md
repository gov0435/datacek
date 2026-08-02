# Panduan Implementation & Operasional: Backup SQLite ke S3

Dokumen ini menjelaskan arsitektur, konfigurasi, cara kerja, pengujian, dan prosedur pemulihan (restore) untuk fitur **Backup Database SQLite ke AWS S3 / S3-Compatible Object Storage** pada aplikasi Expense Tracker.

---

## 1. Overview & Tujuan Fitur

Aplikasi ini menggunakan SQLite sebagai database tunggal (`./db/database.sqlite`). Untuk menjamin keandalan data (*durability*) dan antisipasi *disaster recovery*, dibuat mekanisme backup harian otomatis ke S3.

### Mengapa Menggunakan `VACUUM INTO`?
- **WAL-Safe & Online Hot Backup:** Menggunakan statement SQLite native `VACUUM INTO 'path/to/snapshot.sqlite'`.
- **Tidak Memblokir Live Writes:** Berjalan secara aman pada database live (WAL mode) tanpa memerlukan *downtime* atau *lock* penuh pada aplikasi.
- **Bebas Fragmentasi:** `VACUUM INTO` menghasilkan file snapshot terkompresi yang bersih dari *free pages* tanpa merusak file database utama.

---

## 2. Alur Operasional (Operational Flow)

```text
Scheduler (02:00 AM) / Artisan Manual
  │
  ├── 1. Pre-flight Checks (Validasi driver sqlite, akses file, & ruang disk min 1.5x)
  ├── 2. Snapshot Creation (`VACUUM INTO` ke storage/app/private/sqlite-backups/database-TIMESTAMP-HASH.sqlite)
  ├── 3. Integrity Check (`PRAGMA integrity_check` harus mengembalikan 'ok')
  ├── 4. Hash & Metadata Calculation (Hitung ukuran file & checksum SHA-256)
  ├── 5. Streaming Upload S3 (Upload snapshot & manifest JSON secara private)
  ├── 6. Verification (Verifikasi keberadaan & ukuran file di S3 disk)
  └── 7. Cleanup (Hapus file temporer lokal hanya setelah verifikasi S3 sukses)
```

---

## 3. Konfigurasi Prasyarat & Environment

### Package Composer
Memerlukan Flysystem S3 Driver:
```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### Variabel Lingkungan (`.env`)
Pastikan konfigurasi database dan S3 telah diatur pada `.env`:

```env
# Database Settings
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/project/db/database.sqlite
DB_BUSY_TIMEOUT=5000
DB_JOURNAL_MODE=wal
DB_SYNCHRONOUS=normal

# AWS S3 / Cloudflare R2 / MinIO Settings
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-expense-tracker-backups
AWS_ENDPOINT=                              # Isi jika menggunakan R2, MinIO, atau S3-compatible
AWS_USE_PATH_STYLE_ENDPOINT=false          # Set true jika menggunakan MinIO
```

### Konfigurasi Disk (`config/filesystems.php`)
Command memanfaatkan disk `s3` yang didefinisikan di `config/filesystems.php`:

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
    'report' => false,
],
```

---

## 4. Komponen Kode Terimplementasi

### 4.1 Artisan Command: `php artisan db:backup-sqlite`
File: [`app/Console/Commands/BackupSqliteDatabaseCommand.php`](file:///C:/laragonx/www/expense/app/Console/Commands/BackupSqliteDatabaseCommand.php)

Fitur kunci yang diimplementasikan pada command:
1. **Pre-flight Checks:**
   - Memastikan `database.default` adalah `sqlite`.
   - Memastikan file database lokal ada dan dapat dibaca.
   - Memastikan sisa ruang disk lokal minimal 1.5x dari ukuran file database untuk menampung temporary snapshot.
2. **Atomic Snapshot Generator:**
   - Membuat file temporary unik di `storage/app/private/sqlite-backups/database-YYYYMMDDTHISZ-random.sqlite`.
   - Mengeksekusi PDO Statement `VACUUM INTO ?` dengan parameter binding.
3. **Validasi PRAGMA Integrity:**
   - Membuka file snapshot dan mengeksekusi `PRAGMA integrity_check`. Jika hasil bukan `ok`, backup langsung digagalkan.
4. **Streaming Upload & Manifest Data:**
   - Mengunggah file snapshot menggunakan stream `fopen` agar efisien memori.
   - Membuat manifest JSON sidecar (berisi timestamp, sha256, ukuran bytes, versi app) dan mengunggahnya ke S3 dengan nama yang sama (`.json`).
5. **Verifikasi & Safety Cleanup:**
   - Memeriksa keberadaan object S3 dan kesesuaian ukuran file.
   - Menghapus snapshot lokal hanya apabila upload dan manifest terverifikasi. Jika gagal, exception ditangkap, log error dibuat, file temp dibersihkan, dan command keluar dengan exit code non-zero (`Command::FAILURE`).

### 4.2 Penjadwalan Automated (Scheduler)
File: [`routes/console.php`](file:///C:/laragonx/www/expense/routes/console.php)

```php
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Schedule::command('db:backup-sqlite')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('Scheduler: db:backup-sqlite failed at '.now());
    });
```
- **Waktu Eksekusi:** Setiap hari pukul 02:00 AM.
- **Overlap Protection:** `withoutOverlapping()` mencegah dua job backup berjalan bersamaan jika yang satu belum selesai.
- **Failure Handler:** Mencatat log kesalahan apabila scheduler gagal mengeksekusi command.

---

## 5. Pengujian Terautomasi (Testing)

File Test: [`tests/Feature/BackupSqliteDatabaseCommandTest.php`](file:///C:/laragonx/www/expense/tests/Feature/BackupSqliteDatabaseCommandTest.php)

Pengujian menggunakan Pest PHP dan `Storage::fake('s3')` mencakup:
- Eksekusi `VACUUM INTO` dan streaming upload ke S3.
- Pembuatan file `.sqlite` dan file manifest `.json` di S3.
- Verifikasi bahwa file temporer di lokal berhasil dibersihkan.
- Handling skenario kegagalan (driver bukan SQLite, file DB tidak ditemukan).

### Menjalankan Test
```bash
php artisan test --compact --filter=BackupSqliteDatabaseCommandTest
```

---

## 6. Prosedur Pemulihan (Disaster Recovery / Restore)

Jika terjadi kerusakan data atau kegagalan server, ikuti langkah berikut untuk mengembalikan database dari S3:

### Langkah 1: Unduh Backup & Manifest dari S3
Gunakan AWS CLI / R2 CLI atau dashboard S3 untuk mengunduh snapshot terbaru dan manifest pendampingnya:
```bash
aws s3 cp s3://your-expense-tracker-backups/sqlite-backups/2026/07/31/database-20260731T020000Z-abcd12.sqlite ./restore-target.sqlite
aws s3 cp s3://your-expense-tracker-backups/sqlite-backups/2026/07/31/database-20260731T020000Z-abcd12.json ./manifest.json
```

### Langkah 2: Verifikasi Integritas Snapshot
1. Checksum SHA-256:
   ```bash
   sha256sum ./restore-target.sqlite
   ```
   Bandingkan hasilnya dengan field `"sha256"` di file `manifest.json`.
2. Validasi SQLite Integrity:
   ```bash
   sqlite3 ./restore-target.sqlite "PRAGMA integrity_check;"
   # Output harus: ok
   ```

### Langkah 3: Eksekusi Replacement Database Active
1. Aktifkan Maintenance Mode & Hentikan Queue Worker:
   ```bash
   php artisan down
   # Hentikan worker / container aplikasi
   ```
2. Ganti File Database Utama:
   ```bash
   cp ./restore-target.sqlite ./db/database.sqlite
   chmod 664 ./db/database.sqlite
   ```
3. Nonaktifkan Maintenance Mode:
   ```bash
   php artisan up
   ```

---

## 7. Ringkasan File Terkait dalam Codebase

- [`app/Console/Commands/BackupSqliteDatabaseCommand.php`](file:///C:/laragonx/www/expense/app/Console/Commands/BackupSqliteDatabaseCommand.php) - Class Command utama backup SQLite.
- [`routes/console.php`](file:///C:/laragonx/www/expense/routes/console.php) - Penjadwalan harian 02:00 AM dengan overlap guard.
- [`config/filesystems.php`](file:///C:/laragonx/www/expense/config/filesystems.php) - Definisi disk `s3`.
- [`tests/Feature/BackupSqliteDatabaseCommandTest.php`](file:///C:/laragonx/www/expense/tests/Feature/BackupSqliteDatabaseCommandTest.php) - Unit/Feature test Pest PHP.
- [`.env.example`](file:///C:/laragonx/www/expense/.env.example) - Template variabel lingkungan database & S3.
