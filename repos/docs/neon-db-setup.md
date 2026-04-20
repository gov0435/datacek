# Database Plan: Neon PostgreSQL (Whitelist + Users)

## Overview

Dokumen ini adalah revisi rencana database agar lebih sederhana:

- Gunakan **Neon PostgreSQL** sebagai database utama.
- Fokus hanya pada **2 tabel**:
  1. `whitelists`
  2. `users`
- Login akan memakai **Laravel Socialite**.

---

## Setup Neon PostgreSQL

### 1. Create Neon Project

1. Kunjungi https://neon.tech
2. Sign up / Login
3. Create project baru
4. Copy connection string:

```txt
postgresql://user:password@ep-xxx.neon.tech/dbname?sslmode=require
```

### 2. Configure Environment

Di file `.env`:

```env
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://user:password@ep-xxx.neon.tech/dbname?sslmode=require
```

### 3. Run Migration

```bash
php artisan migrate --database=pgsql
```

---

## Schema Plan

## 1) Table `whitelists`

Tujuan: sumber data izin login.

Kolom inti:

- `email` (string, unique)
- `nama` (string)
- `instansi` (string)

Kolom standar Laravel (disarankan):

- `id` (bigint)
- `created_at`, `updated_at`

Contoh struktur:

```txt
whitelists
- id (bigint)
- email (varchar, UNIQUE)
- nama (varchar)
- instansi (varchar)
- created_at, updated_at
```

## 2) Table `users` (disesuaikan untuk Socialite)

Tujuan: menyimpan user aplikasi hasil login OAuth.

Kolom inti:

- `name` (string)
- `email` (string, unique)
- `instansi` (string, nullable) -> diisi dari tabel `whitelists`
- `provider` (string, nullable) -> contoh: `google`, `github`
- `provider_id` (string, nullable) -> ID user dari provider OAuth
- `avatar` (text/string, nullable)
- `role` (string, default: `member`)

Kolom autentikasi Laravel:

- `email_verified_at` (timestamp, nullable)
- `remember_token` (string, nullable)
- `created_at`, `updated_at`

Index penting:

- unique: `email`
- index gabungan: (`provider`, `provider_id`)

Contoh struktur:

```txt
users
- id (bigint)
- name (varchar)
- email (varchar, UNIQUE)
- instansi (varchar, nullable)
- provider (varchar, nullable)
- provider_id (varchar, nullable)
- avatar (text, nullable)
- role (varchar, default: 'member')
- email_verified_at (timestamp, nullable)
- remember_token (varchar, nullable)
- created_at, updated_at

Indexes:
- UNIQUE(email)
- INDEX(provider, provider_id)
```

---

## Login Flow (Socialite + Whitelist)

1. User login via Socialite (`/auth/{provider}/redirect`, `/auth/{provider}/callback`).
2. Ambil data dari provider: `id`, `name`, `email`, `avatar`.
3. Cek `email` ada di tabel `whitelists`.
4. Jika **tidak ada di whitelist** -> langsung redirect ke halaman `no-auth` (tanpa membuat/mengubah data `users`).
5. Jika **ada di whitelist**, cek apakah user dengan email tersebut sudah ada di tabel `users`.
6. Jika user **sudah ada** -> langsung login/authenticate dan lanjut ke halaman aplikasi.
7. Jika user **belum ada** -> simpan credential user ke tabel `users` (name, email, instansi, provider, provider_id, avatar, role=member), lalu login/authenticate.

---

## Migration Plan (yang akan dibuat)

1. Migration create table `whitelists`.
2. Sesuaikan migration table `users` agar ada kolom Socialite:
   - `provider`
   - `provider_id`
   - `avatar`
   - `instansi`
   - `role` (default: `member`)
3. Tambahkan index `provider + provider_id`.

---

## Notes

- Jika saat callback Socialite ada provider yang tidak mengirim email, login sebaiknya ditolak (karena proses whitelist berbasis email).
- Jika nanti ingin multi-provider per user (misal satu user bisa Google + GitHub), opsi terbaik biasanya tabel terpisah `social_accounts`. Namun untuk scope saat ini tetap pakai 2 tabel sesuai permintaan.
- Route/halaman `no-auth` dipakai sebagai landing page saat email tidak lolos whitelist.
