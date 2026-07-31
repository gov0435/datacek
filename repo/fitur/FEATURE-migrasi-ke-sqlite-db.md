# Feature: Migrasi Database Utama Aplikasi ke SQLite lokal (/db/neon_data.sqlite)

> Dokumen ini adalah rencana pengalihan database utama aplikasi dari Neon PostgreSQL ke SQLite lokal.  
> Dibuat berdasarkan PRD: `repos/docs/neon-db-setup.md` & `repo/fitur/FEATURE-backup-neon-to-sqlite.md` | Versi: 31 Juli 2026

---

## 1. Overview

**Nama Fitur:** Migrasi Database Utama ke SQLite (`db/neon_data.sqlite`)  
**Status:** Draft  
**Priority:** High  
**Epic/Module:** Infrastructure & Database Configuration  
**Detected Stack:** Laravel 13 + PHP 8.4 + SQLite 3 + Filament v5 + Pest 4

### Problem Statement
Saat ini aplikasi terkonfigurasi menggunakan database cloud Neon PostgreSQL (`DB_CONNECTION=pgsql`). Untuk mempercepat latensi query lokal, mendukung mode offline, dan membebaskan ketergantungan dari koneksi internet ke cloud, aplikasi perlu dialihkan agar membaca dan menulis langsung ke file database SQLite yang tersimpan di `db/neon_data.sqlite`.

### Proposed Solution
Mengubah konfigurasi `.env` (serta menyesuaikan `config/database.php` jika diperlukan) sehingga `DB_CONNECTION=sqlite` dan `DB_DATABASE=C:\laragonx\www\ppg26\db\neon_data.sqlite` (atau `database_path('../db/neon_data.sqlite')`). Memastikan seluruh Eloquent Model, Filament Panel, Session Store, Cache, dan Service berjalan lancar di atas database SQLite.

---

## 2. Alignment dengan PRD

| Aspek | Keterangan |
|-------|------------|
| **Product Goal** | Meningkatkan kecepatan akses data & kemudahan pengujian lokal tanpa latensi network ke Neon DB (`repos/docs/neon-db-setup.md`). |
| **Target User** | Developer / Administrator & Pengguna Aplikasi. |
| **Scope** | ✅ In scope (Pengalihan `DB_CONNECTION` dari `pgsql` ke `sqlite`). |
| **Dependency** | Keberadaan file `db/neon_data.sqlite` yang berisi schema dan data hasil backup/sync. |

---

## 3. User Flow

**Happy Path:**
```
[Update Konfigurasi .env] → [Clear Cache Config/Route/App] → [Aplikasi Terhubung ke SQLite /db/neon_data.sqlite] → [Uji Coba Read/Write Data & Login Socialite/Whitelist] → [Sistem Berjalan Penuh secara Offline/Lokal]
```

**Edge Cases:**
- [ ] Apa yang terjadi jika file `db/neon_data.sqlite` belum ada? (Sistem menampilkan petunjuk untuk menjalankan `php artisan db:backup-neon-sqlite` terlebih dahulu).
- [ ] Apa yang terjadi jika ada kueri mentah (raw SQL) yang spesifik untuk PostgreSQL? (Sesuaikan kueri agar kompatibel dengan ANSI SQL / SQLite).
- [ ] Apa yang terjadi saat menjalankan migration baru di SQLite? (Memastikan driver `sqlite` mendukung penambahan/perubahan kolom tanpa sintaks PostgreSQL-only).

---

## 4. Functional Requirements

### Must Have (MVP)
- [ ] Mengubah `DB_CONNECTION` pada `.env` menjadi `sqlite`.
- [ ] Mengatur path database pada `.env` atau `config/database.php` mengarah ke `db/neon_data.sqlite`.
- [ ] Memastikan `php artisan config:clear` dan `php artisan test` berjalan tanpa kendala di driver SQLite.
- [ ] Memastikan fitur autentikasi whitelist (`whitelists`) dan login user (`users`) berfungsi di SQLite.

### Should Have
- [ ] Opsi penambahan koneksi fallback ke `pgsql` di `config/database.php` jika sewaktu-waktu ingin membaca dari Neon DB kembali.

### Won't Have (untuk versi ini)
- [ ] Perubahan arsitektur tabel (struktur data tetap persis sama dengan yang ada di Neon DB).

---

## 5. Non-Functional Requirements

| Aspek | Requirement |
|-------|-------------|
| **Performance** | Response time kueri database lokal < 50ms (bebas latensi jaringan cloud). |
| **Compatibility** | Seluruh unit/feature test Pest lulus menggunakan driver `sqlite`. |
| **Portability** | File database `db/neon_data.sqlite` dapat dipindah/dibackup dengan mudah tanpa instalasi service PostgreSQL lokal. |

---

## 6. UI/UX Notes

**Touchpoints:**
- Tidak ada perubahan pada tampilan UI/UX Filament maupun Frontend. Perubahan sepenuhnya terjadi pada layer konfigurasi infrastructure & database connection.

---

## 7. Technical Plan

### Existing Architecture
- **Framework:** Laravel 13.x dengan PHP 8.4.
- **Konfigurasi Aktif:** `.env` menunjuk `DB_CONNECTION=pgsql` dengan `DB_URL` Neon PostgreSQL.
- **File Database Lokal:** `db/neon_data.sqlite` sudah berisi 15 tabel dan data lengkap.

### Implementation Impact
| Layer | Perubahan | Lokasi/Komponen |
|-------|-----------|-----------------|
| Environment | Ubah `DB_CONNECTION` & `DB_DATABASE` | `.env` |
| Configuration | Sesuaikan path default SQLite jika menggunakan relatif | `config/database.php` |
| Verification | Pengujian fitur & rute aplikasi | Pest Feature Tests & Artisan Command |

### Backend — Laravel
- **Konfigurasi `.env`:**
  ```env
  DB_CONNECTION=sqlite
  DB_DATABASE="${APP_BASE_PATH}/db/neon_data.sqlite"
  # atau path relatif ke base_path('db/neon_data.sqlite')
  ```
- **Clear Cache:** `php artisan config:clear`

### Security & Privacy
- File `db/neon_data.sqlite` tetap masuk dalam `.gitignore` agar data sensitif tidak ter-push ke publik.

### Testing Strategy
| Level | Skenario | Tool Existing |
|-------|----------|---------------|
| Backend | Menjalankan seluruh test suite untuk memastikan fungsi CRUD & Autentikasi berjalan di SQLite | Pest 4 (`php artisan test`) |

---

## 8. Acceptance Criteria

Fitur dinyatakan selesai jika:
- [ ] Konfigurasi `.env` berhasil diubah ke `DB_CONNECTION=sqlite` mengarah ke `db/neon_data.sqlite`.
- [ ] `php artisan migrate:status` atau `php artisan db:show` mengindikasikan koneksi aktif adalah SQLite di `/db/neon_data.sqlite`.
- [ ] Aplikasi (Filament Admin, Auth, API Lookup) berjalan lancar tanpa error koneksi PostgreSQL.
- [ ] Seluruh test suite (`php artisan test`) lulus 100%.

---

## 9. Open Questions

- [ ] Apakah path database di `.env` sebaiknya menggunakan path absolut (seperti `C:\laragonx\www\ppg26\db\neon_data.sqlite`) atau relatif via `config/database.php`? *(Rekomendasi: sesuaikan `config/database.php` agar secara default membaca `base_path('db/neon_data.sqlite')` jika ada).*

---

## 10. Timeline Estimasi

| Fase | Estimasi | Keterangan |
|------|----------|------------|
| Design & Spec | 0.5 hari | Dokumen spesifikasi (`FEATURE-migrasi-ke-sqlite-db.md`) |
| Development | 0.5 hari | Update `.env`, `config/database.php`, & clear cache |
| Testing | 0.5 hari | Menjalankan Pest test suite & verifikasi rute aplikasi |
| Release | Segera | Aplikasi resmi menggunakan SQLite lokal |

**Confidence:** High — Database SQLite `db/neon_data.sqlite` sudah terisi data lengkap dan teruji.
