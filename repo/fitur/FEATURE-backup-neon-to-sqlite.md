# Feature: Script Backup Neon Database ke SQLite

> Dokumen ini adalah rencana pengembangan fitur Backup Neon Database ke SQLite.  
> Dibuat berdasarkan PRD: `repos/docs/neon-db-setup.md` | Versi: 31 Juli 2026

---

## 1. Overview

**Nama Fitur:** Script Backup Neon Database ke SQLite  
**Status:** Draft  
**Priority:** Medium  
**Epic/Module:** Database & Maintenance  
**Detected Stack:** Laravel 13 + PHP 8.4 + Neon PostgreSQL (`pgsql`) + SQLite 3 (`sqlite`) + Pest 4

### Problem Statement
Aplikasi bergantung pada Neon PostgreSQL di cloud. Untuk keperluan pengujian lokal, arsip offline, pencegahan kehilangan data, serta mitigasi koneksi internet yang tidak stabil, dibutuhkan mekanisme backup otomatis/manual yang mengekstrak seluruh data dari Neon DB dan menyimpannya ke dalam file database SQLite lokal di folder `/db`.

### Proposed Solution
Menyediakan skrip Artisan Command Laravel (misalnya `php artisan db:backup-neon-sqlite`) dan/atau skrip PHP CLI independen yang mengoneksikan instance Neon PostgreSQL, membaca seluruh skema dan tabel (`whitelists`, `users`, dsb.), mentranslasikan tipe data PostgreSQL ke SQLite, lalu membuat/memperbarui file database SQLite di direktori `/db` (misalnya `db/backup_neon_[timestamp].sqlite` atau `db/neon_backup.sqlite`).

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Menjaga portabilitas data dan cadangan offline dari database utama Neon PostgreSQL (`repos/docs/neon-db-setup.md`). |
| **Target User** | Developer / Administrator sistem. |
| **Scope** | ✅ In scope (Dukungan backup dari koneksi `pgsql` ke `sqlite`). |
| **Dependency** | Koneksi `pgsql` ke Neon DB aktif (`DATABASE_URL` atau `DB_*` di `.env`), ekstensi `pdo_sqlite` & `pdo_pgsql` aktif di PHP. |

---

## 3. User Flow

**Happy Path:**
```
[Jalankan Command / Script] → [Validasi Koneksi Neon DB & SQLite] → [Pastikan Direktori /db Ada] → [Dump Schema & Rows PostgreSQL] → [Generate / Write Ke SQLite File] → [Print Log Sukses & Ringkasan Data]
```

**Edge Cases:**
- [ ] Apa yang terjadi jika koneksi ke Neon PostgreSQL timeout/gagal? (Sistem menampilkan error log ringkas dan membatalkan proses tanpa merusak backup sebelumnya).
- [ ] Apa yang terjadi jika direktori `/db` belum ada? (Script otomatis membuat folder `/db` jika belum ada).
- [ ] Apa yang terjadi jika terdapat tipe data khas PostgreSQL (misal `jsonb`, `uuid`, `timestamp with time zone`) yang perlu dimasukkan ke SQLite? (Translasikan ke tipe data yang didukung SQLite seperti `TEXT` atau `INTEGER`).
- [ ] Apa yang terjadi jika file SQLite target sudah ada? (Menyediakan parameter `--overwrite` atau meng-generate file bertanggal `db/backup_YYYY-MM-DD_HHmmss.sqlite`).

---

## 4. Functional Requirements

### Must Have (MVP)
- [ ] Script / Artisan Command `db:backup-neon-sqlite` untuk memicu backup data.
- [ ] Membuat direktori `/db` secara otomatis jika belum tersedia di root project.
- [ ] Membaca semua tabel yang ada di skema Neon PostgreSQL (khususnya tabel `whitelists`, `users`, `migrations`, dll.).
- [ ] Mentransfer struktur skema beserta seluruh baris data ke file SQLite (`.sqlite`).
- [ ] Menampilkan ringkasan proses (jumlah tabel, total baris ter-copy, nama file SQLite hasil backup).

### Should Have
- [ ] Opsi `--filename=` untuk kustomisasi nama file hasil backup di folder `/db`.
- [ ] Opsi `--compress` atau zip arsip hasil backup SQLite.
- [ ] Logging detail kegagalan per tabel jika terjadi error konversi data.

### Won't Have (untuk versi ini)
- [ ] GUI / Dashboard Filament untuk scheduler backup (cukup via CLI & Laravel Scheduler terlebih dahulu).
- [ ] Restoration otomatis dari SQLite balik ke Neon DB (fitur ini khusus arah Backup Neon → SQLite).

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | Memproses eksekusi chunking (misal `chunk(500)`) agar aman memori untuk tabel berukuran ribuan baris. |
| **Security** | File `/db/*.sqlite` didaftarkan ke `.gitignore` agar file kredensial/data lokal sensitif tidak sengaja ter-commit ke Git repository. |
| **Reliability** | Menggunakan transaksi database (`DB::transaction`) saat penulisan ke SQLite agar bersifat atomic per tabel/proses. |
| **Availability** | Dapat dijalankan via CLI kapan saja atau dijadwalkan via `routes/console.php` (Laravel Scheduler). |

---

## 6. UI/UX Notes (CLI Interface)

**Touchpoints:**
- Output terminal menggunakan Laravel Prompts / Console Formatting (misal: `<info>Backup completed: db/neon_backup_20260731.sqlite</info>`).
- Progress bar saat menyalin data dari tabel besar.

---

## 7. Technical Plan

### Existing Architecture
- **Framework:** Laravel 13.x dengan PHP 8.4.
- **Database Driver:** `pgsql` (Neon PostgreSQL) & `sqlite` (SQLite 3).
- **Environment config:** Kredensial Neon tersimpan di `.env` (`DB_CONNECTION=pgsql`, `DATABASE_URL=...`).

### Implementation Impact
| Layer | Perubahan | Lokasi/Komponen |
|-------|-----------|-----------------|
| Backend | Artisan Command baru | `app/Console/Commands/BackupNeonToSqliteCommand.php` |
| Configuration | Pendaftaran command/schedule (opsional) | `routes/console.php` |
| Environment | Penambahan `.gitignore` rule | `.gitignore` (`/db/*.sqlite`) |
| Storage / DB | Folder lokal penampung backup | `/db/` |

### Backend — Laravel
- **Command:** `php artisan db:backup-neon-sqlite`
- **Logic:**
  1. Buat koneksi runtime SQLite mengarahkan ke `base_path('db/' . $filename)`.
  2. Dapatkan daftar tabel dari Neon PG via `Schema::connection('pgsql')->getTableListing()`.
  3. Abaikan tabel sistem internal PG seperti `spatial_ref_sys` jika ada.
  4. Untuk setiap tabel:
     - Dapatkan kolom dan tipe data.
     - Buat tabel di SQLite via `Schema::connection('sqlite_backup')`.
     - Ambil baris data dalam chunking (`DB::connection('pgsql')->table($table)->orderBy('id')->chunk(...)`).
     - Insert ke tabel SQLite.
- **Console Log:** Output berwarna menggunakan `$this->info()`, `$this->warn()`, dan `$this->output->progressStart()`.

### Security & Privacy
- [ ] Menambahkan `/db/*.sqlite` ke `.gitignore` agar database dump tidak ter-push ke versi kontrol.
- [ ] Memastikan file SQLite lokal dibuat dengan permission file yang aman (`0600` / `0644`).

### Testing Strategy
| Level | Skenario | Tool Existing |
|-------|----------|---------------|
| Backend | Feature test untuk Artisan Command `db:backup-neon-sqlite` (memastikan file SQLite tercipta dan tabel terisi) | Pest 4 (`tests/Feature/BackupNeonToSqliteTest.php`) |

### Operational Impact
- **Folder Structure:** Dibuat folder `/db` di root workspace (`base_path('db')`).
- **Dependencies:** Menggunakan dependency internal Laravel (`Illuminate\Support\Facades\DB`, `Illuminate\Support\Facades\Schema`, `Illuminate\Support\Facades\File`).

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [ ] Command `php artisan db:backup-neon-sqlite` sukses dieksekusi via CLI tanpa error.
- [ ] Folder `/db` berhasil dibuat otomatis jika belum ada.
- [ ] File SQLite berformat `.sqlite` tercipta di folder `/db` berisi skema dan seluruh data dari database Neon.
- [ ] Data pada tabel `whitelists` dan `users` (dan tabel Laravel lainnya) ter-copy dengan utuh ke SQLite.
- [ ] File `.gitignore` diperbarui untuk mengecualikan file SQLite di `/db`.
- [ ] Test Pest (`tests/Feature/BackupNeonToSqliteTest.php`) berhasil lulus (green).

---

## 9. Open Questions (Resolved)

- [x] **Target Filename:** Nama file backup disepakati secara default adalah `db/neon_data.sqlite`.
- [x] **Scope Data:** Semua tabel dan isinya dari database Neon PostgreSQL diekstrak dan disalin penuh ke SQLite.


---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Design & Spec | 0.5 hari | Finalisasi dokumen perancangan (`FEATURE-backup-neon-to-sqlite.md`) |
| Development | 1 hari | Implementasi `BackupNeonToSqliteCommand.php` & pengaturan `.gitignore` |
| Testing | 0.5 hari | Penulisan Pest Test & verifikasi data transfer |
| Release | Target Segera | Siap digunakan untuk lokal backup |

**Confidence:** High — Menggunakan fitur bawaan Laravel Eloquent/Schema builder & SQLite driver yang sudah mature.
