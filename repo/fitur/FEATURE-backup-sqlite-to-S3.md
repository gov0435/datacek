# Feature: Backup Database SQLite ke AWS S3

> Dokumen ini adalah rencana pengembangan fitur Backup Database SQLite ke AWS S3 / S3-Compatible Storage.  
> Dibuat berdasarkan PRD: `repo/docs/backup-sqlite-to-S3.md` | Versi: 31 Juli 2026

---

## 1. Overview

**Nama Fitur:** Backup Database SQLite ke AWS S3  
**Status:** Draft  
**Priority:** High  
**Epic/Module:** Infrastructure & Disaster Recovery  
**Detected Stack:** Laravel 13 + PHP 8.4 + SQLite 3 + Pest 4 + league/flysystem-aws-s3-v3

### Problem Statement
Aplikasi menggunakan SQLite sebagai database utama (`./db/database.sqlite`). Untuk menjamin keandalan data (*durability*) dan kesiapan pemulihan bencana (*disaster recovery*), diperlukan mekanisme backup database otomatis dan manual ke Object Storage S3 tanpa menyebabkan *downtime* atau mengganggu operasi read/write aplikasi live.

### Proposed Solution
Mengimplementasikan Artisan Command `php artisan db:backup-sqlite` yang mengeksekusi hot snapshot WAL-safe menggunakan statement native `VACUUM INTO`, melakukan pengecekan integritas `PRAGMA integrity_check`, membuat manifest sidecar JSON dengan checksum SHA-256, lalu mengunggahnya secara streaming ke AWS S3 / S3-compatible storage. Fitur ini dijadwalkan secara otomatis setiap hari pukul 02:00 AM via Laravel Scheduler.

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Menjamin keandalan data (*durability*) dan kesiapan *disaster recovery* melalui backup otomatis off-site ke S3 (`repo/docs/backup-sqlite-to-S3.md`). |
| **Target User** | System Administrator, Developer, & Automated Scheduler. |
| **Scope** | ✅ In scope (Artisan Command backup, pre-flight checks, `VACUUM INTO`, PRAGMA integrity, upload S3 streaming, manifest sidecar JSON, scheduler entry, dan Disaster Recovery restore procedure). |
| **Dependency** | Database SQLite (`.env` `DB_CONNECTION=sqlite`), package `league/flysystem-aws-s3-v3`, serta kredensial `AWS_*` pada `.env`. |

---

## 3. User Flow

**Happy Path (Automated & Manual):**
```
[Trigger: Scheduler 02:00 / php artisan db:backup-sqlite] 
   → [Pre-flight Checks: Driver sqlite, DB file exists, Space >= 1.5x DB size] 
   → [Hot Snapshot: VACUUM INTO 'storage/app/private/sqlite-backups/database-TIMESTAMP-HASH.sqlite'] 
   → [Integrity Check: PRAGMA integrity_check == 'ok'] 
   → [Gzip Compression: Compress snapshot ke database-TIMESTAMP-HASH.sqlite.gz] 
   → [Calculate Checksum SHA-256 & Generate Manifest JSON Sidecar] 
   → [Streaming Upload .sqlite.gz & .json ke S3: backups/kawal-ppg/YYYY-MM-DD/HHMMSS-database.sqlite.gz] 
   → [Verification: Cek keberadaan & ukuran file di S3] 
   → [Safety Cleanup: Hapus snapshot & manifest temporer lokal] 
   → [Complete: Log Info & Exit Code 0]
```

**Edge Cases:**
- [x] **Driver DB bukan SQLite:** Command membatalkan eksekusi, mencetak error ke console & log, lalu exit dengan status `Command::FAILURE`.
- [x] **Ruang disk lokal < 1.5x ukuran database:** Command gagal di tahap pre-flight sebelum `VACUUM INTO` untuk mencegah disk kehabisan ruang.
- [x] **Snapshot corrupt (`PRAGMA integrity_check != 'ok'`):** Command membatalkan proses upload, menghapus snapshot rusak, dan mencatat alert error.
- [x] **Koneksi / Upload ke S3 Gagal:** Command menangkap Exception, mempertahankan/membersihkan file temp lokal dengan aman, dan memicu error handler scheduler.
- [x] **Scheduler Overlap:** Opsi `withoutOverlapping()` mencegah dua eksekusi backup berjalan bersamaan secara bersamaan.

---

## 4. Functional Requirements

### Must Have (MVP)
- [x] Artisan Command `db:backup-sqlite` (`app/Console/Commands/BackupSqliteDatabaseCommand.php`).
- [x] Pre-flight checks: Pengujian `DB_CONNECTION === 'sqlite'`, ketersediaan file DB, dan pemeriksaan sisa disk minimal 1.5x ukuran DB.
- [x] Snapshot generation menggunakan statement SQLite native `VACUUM INTO` ke path temporary unik di `storage/app/private/sqlite-backups/`.
- [x] Validation integritas snapshot menggunakan query `PRAGMA integrity_check`.
- [x] Perhitungan metadata file (bytes size) dan SHA-256 checksum untuk pembuatan file manifest JSON sidecar.
- [x] Streaming upload file snapshot (`.sqlite`) dan manifest (`.json`) ke disk `s3`.
- [x] Post-upload verification (memastikan file di S3 ada dan berukuran sesuai) diikuti penghapusan file temporer lokal.
- [x] Penjadwalan harian otomatis di `routes/console.php` pukul 02:00 AM dengan `withoutOverlapping()`.
- [x] Konfigurasi disk `s3` di `config/filesystems.php` dan opsi `.env`.

### Should Have
- [x] Parameter/flag CLI opsional pada command (misal `--keep-local`) jika pengguna ingin menyimpan salinan backup di lokal.
- [x] Logging komprehensif log aktivitas backup (start, snapshot duration, upload size, verification result).

### Won't Have (untuk versi ini)
- [ ] Dashboard GUI web untuk manajemen/download backup S3 (eksekusi via CLI/Scheduler & AWS/R2 Management Console/CLI).
- [ ] Automated cleanup via PHP script (retensi 7 hari diserahkan dan diatur via S3 Bucket Lifecycle Expiration Rule 7 Hari pada provider S3-compatible).

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | Menggunakan `VACUUM INTO` (WAL mode) tanpa memblokir write/read transaksi live aplikasi. |
| **Memory Efficiency** | Menggunakan PHP stream `fopen` saat upload ke S3 untuk mencegah konsumsi RAM membengkak pada database besar. |
| **Security** | Backup & manifest diunggah secara private ke S3 bucket. Kredensial AWS disimpan aman di `.env`. |
| **Durability & Reliability** | Verifikasi 2-tahap: `PRAGMA integrity_check` sebelum upload, dan verifikasi objek S3 sebelum penghapusan temp lokal. |

---

## 6. UI/UX Notes

**Touchpoints:**
- Tidak ada UI web. Antarmuka fitur sepenuhnya berbasis Command Line Interface (Artisan CLI) dan log output.
- Log output CLI terformat rapi dengan status indikator (`info`, `error`, `warn`).

---

## 7. Technical Plan

### Existing Architecture
- **Framework:** Laravel 13.x (PHP 8.4)
- **Database Driver:** SQLite 3 (`DB_CONNECTION=sqlite`, `DB_DATABASE=db/database.sqlite` / `db/neon_data.sqlite`)
- **Package Upload:** `league/flysystem-aws-s3-v3` (^3.0)
- **Test Engine:** Pest 4 (`pestphp/pest`)

### Implementation Impact
| Layer | Perubahan | Lokasi/Komponen |
|-------|-----------|-----------------|
| Backend CLI | Membuat Artisan Command `db:backup-sqlite` | `app/Console/Commands/BackupSqliteDatabaseCommand.php` |
| Infrastructure | Konfigurasi disk S3 & env template | `config/filesystems.php`, `.env.example` |
| Scheduler | Pendaftaran jadwal harian 02:00 AM | `routes/console.php` |
| Testing | Feature Test Pest PHP dengan `Storage::fake('s3')` | `tests/Feature/BackupSqliteDatabaseCommandTest.php` |

### Backend — Laravel
- **Artisan Command Class:** `app/Console/Commands/BackupSqliteDatabaseCommand.php`
  - Signature: `db:backup-sqlite`
  - Description: `Backup SQLite database to AWS S3 using native VACUUM INTO snapshot`
- **Konfigurasi `.env` & `.env.example`:**
  ```env
  AWS_ACCESS_KEY_ID=your_access_key
  AWS_SECRET_ACCESS_KEY=your_secret_key
  AWS_DEFAULT_REGION=us-east-1
  AWS_BUCKET=your-expense-tracker-backups
  AWS_ENDPOINT=
  AWS_USE_PATH_STYLE_ENDPOINT=false
  ```
- **Konfigurasi `config/filesystems.php`:**
  - Menambahkan/memastikan disk `s3` terkonfigurasi dengan variabel `AWS_*`.
- **Penjadwalan `routes/console.php`:**
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

### Disaster Recovery / Restore Procedure
1. Unduh snapshot `.sqlite` & `.json` dari S3.
2. Verifikasi checksum `sha256sum` terhadap file manifest `.json`.
3. Verifikasi integritas SQLite: `sqlite3 ./restore-target.sqlite "PRAGMA integrity_check;"`.
4. Aktifkan maintenance mode (`php artisan down`), ganti file `./db/database.sqlite`, lalu jalankan `php artisan up`.

### Security & Privacy
- Kredensial AWS (Access Key & Secret) tidak hardcoded dan hanya dibaca via `.env`.
- File snapshot temporer disimpan di `storage/app/private/sqlite-backups/` yang tidak terakses secara publik via web server.

### Testing Strategy
| Level | Skenario | Tool Existing |
|-------|----------|---------------|
| Feature Test | Menguji `VACUUM INTO`, pembuatan snapshot, pembuatan manifest JSON, streaming upload ke `Storage::fake('s3')`, dan pembersihan file temporer lokal | Pest 4 (`tests/Feature/BackupSqliteDatabaseCommandTest.php`) |
| Edge Cases Test | Menguji kegagalan driver non-SQLite dan kondisi database tidak ditemukan | Pest 4 |

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [x] Command `php artisan db:backup-sqlite` berhasil dieksekusi tanpa error di lingkungan SQLite.
- [x] File snapshot `.sqlite` dan manifest sidecar `.json` berhasil diunggah ke S3 disk.
- [x] Manifest JSON berisi timestamp, sha256 checksum, ukuran file (bytes), dan versi aplikasi.
- [x] Integrity check `PRAGMA integrity_check` berhasil mevalidasi snapshot sebelum upload.
- [x] File snapshot temporer di lokal dibersihkan secara otomatis setelah upload terverifikasi.
- [x] Scheduler `db:backup-sqlite` terdaftar di `routes/console.php` (setiap 02:00 AM dengan `withoutOverlapping`).
- [x] Pest test suite `php artisan test --filter=BackupSqliteDatabaseCommandTest` lulus 100%.

---

## 9. Open Questions (Resolved)

- [x] **Provider S3 & Compatibility:** Menggunakan S3-compatible Object Storage (AWS S3, Cloudflare R2, MinIO, DigitalOcean Spaces, dsb.) yang fleksibel dikonfigurasi via `AWS_ENDPOINT` dan `AWS_USE_PATH_STYLE_ENDPOINT` di `.env`.
- [x] **Retensi Backup di S3:** Retensi ditentukan selama **7 hari**. File backup yang berumur lebih dari 7 hari akan dibersihkan secara otomatis (dapat dikonfigurasi via S3 Lifecycle Expiration Rule 7 Hari pada bucket S3 / S3-Compatible Storage).

---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Design & Spec | 0.5 hari | Finalisasi dokumen `FEATURE-backup-sqlite-to-S3.md` |
| Development | 1 hari | Implementasi Command `BackupSqliteDatabaseCommand`, config `filesystems.php`, & `routes/console.php` |
| Testing | 0.5 hari | Penulisan & verifikasi Pest test `BackupSqliteDatabaseCommandTest` |
| Release | 0.5 hari | Konfigurasi `.env` server & pengujian manual end-to-end |

**Confidence:** High — Spesifikasi teknis dan alur operasional `VACUUM INTO` sudah terdokumentasikan lengkap di `repo/docs/backup-sqlite-to-S3.md`.
