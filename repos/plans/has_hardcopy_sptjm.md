# Rencana Implementasi Fitur Flag Hardcopy SPTJM Sekolah

Fitur ini dirancang untuk menambahkan flag penanda bahwa dokumen fisik/hardcopy SPTJM (Surat Pernyataan Tanggung Jawab Mutlak) dari sekolah telah diterima oleh Dinas. 

Kriteria implementasi:
1. Nama kolom database: `has_hardcopy` (boolean, default false).
2. Perubahan database dikirim dalam format raw SQL script untuk dieksekusi langsung pada database Neon.
3. Toggle pengeditan `has_hardcopy` dibatasi secara eksklusif hanya untuk pengguna dengan role `admin` dan `kgtk` via action baris (`recordActions`).
4. Pada kolom tabel, status `has_hardcopy` ditampilkan menggunakan `IconColumn` read-only.
5. Pengunggahan PDF oleh `kgtk` otomatis menandai `has_hardcopy = true`, sedangkan oleh `member` tetap `false` (atau di-reset menjadi `false`).
6. Penggunaan ikon pada aksi baris menggunakan enum `Heroicon`.

---

## 1. Migrasi Basis Data (SQL Script untuk Neon DB)

Eksekusi perintah SQL berikut secara langsung di Neon SQL Editor / client database Anda:

```sql
-- Tambah kolom has_hardcopy ke tabel sptjm_sekolah setelah kolom is_valid
ALTER TABLE sptjm_sekolah ADD COLUMN has_hardcopy BOOLEAN DEFAULT FALSE;
```

### Pembaruan File SQL Repositori
Perbarui berkas [create_sptjm_dokumen_tables.sql](file:///C:/laragonx/www/ppg26/repos/sql/create_sptjm_dokumen_tables.sql) pada bagian pembuatan tabel `sptjm_sekolah`:
```sql
CREATE TABLE sptjm_sekolah (
    ...
    is_valid BOOLEAN DEFAULT FALSE,
    has_hardcopy BOOLEAN DEFAULT FALSE, -- Tambahkan kolom ini di script mentah
    generated_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    ...
);
```

---

## 2. Pembaruan Model (`SptjmSekolah`)

Buka berkas [SptjmSekolah.php](file:///C:/laragonx/www/ppg26/app/Models/SptjmSekolah.php):
* Tambahkan `'has_hardcopy'` ke array `$fillable`.
* Tambahkan `'has_hardcopy' => 'boolean'` pada casts.

---

## 3. Logika Upload PDF Otomatis ([SptjmsTable.php](file:///C:/laragonx/www/ppg26/app/Filament/App/Resources/Sptjm/Tables/SptjmsTable.php))

Pada callback `action` unggah dokumen di detail modal:
```php
DB::transaction(function () use ($data, $record): void {
    $record->unggahan()->create([
        'disk' => 's3',
        'file_path' => $data['file'],
        'file_name' => basename($data['file']),
        'file_mime' => 'application/pdf',
        'file_size' => null,
        'catatan' => $data['catatan'] ?? null,
        'uploaded_by' => Auth::id(),
    ]);

    // Logika otomatis berdasarkan role pengunggah
    if (Auth::user()?->isKgtk()) {
        $record->update(['has_hardcopy' => true]);
    } else {
        $record->update(['has_hardcopy' => false]);
    }
});
```

---

## 4. Pembaruan Tampilan & Hak Akses (Filament)

### A. Tabel App Portal ([SptjmsTable.php](file:///C:/laragonx/www/ppg26/app/Filament/App/Resources/Sptjm/Tables/SptjmsTable.php))
Status hardcopy ditampilkan sebagai `IconColumn` (read-only checkbox):
```php
IconColumn::make('has_hardcopy')
    ->label('Hardcopy')
    ->boolean()
    ->sortable(),
```

Pengeditan dilakukan melalui `recordActions` menggunakan action `toggle_hardcopy` dengan enum `Heroicon` yang dibatasi hanya untuk user `admin` dan `kgtk`:
```php
Action::make('toggle_hardcopy')
    ->icon(fn (SptjmSekolah $record): Heroicon => $record->has_hardcopy ? Heroicon::XCircle : Heroicon::CheckCircle)
    ->color('gray')
    ->tooltip(fn (SptjmSekolah $record): string => $record->has_hardcopy ? 'Tandai Hardcopy Belum Diterima' : 'Tandai Hardcopy Sudah Diterima')
    ->action(function (SptjmSekolah $record): void {
        $record->update(['has_hardcopy' => ! $record->has_hardcopy]);

        Notification::make()
            ->title($record->has_hardcopy
                ? "Hardcopy {$record->sekolah_nama} ditandai Ada"
                : "Hardcopy {$record->sekolah_nama} ditandai Tidak Ada"
            )
            ->success()
            ->send();
    })
    ->visible(fn (): bool => in_array(Auth::user()?->role, ['admin', 'kgtk'], true)),
```

### B. Filter Tabel ([SptjmsTable.php](file:///C:/laragonx/www/ppg26/app/Filament/App/Resources/Sptjm/Tables/SptjmsTable.php))
Tambahkan opsi pencarian berdasarkan status hardcopy:
```php
SelectFilter::make('has_hardcopy')
    ->label('Status Hardcopy')
    ->options([
        '1' => 'Hardcopy Ada',
        '0' => 'Hardcopy Tidak Ada',
    ])
```

### C. Tabel Admin Portal ([SptjmSekolahsTable.php](file:///C:/laragonx/www/ppg26/app/Filament/Resources/SptjmSekolahs/Tables/SptjmSekolahsTable.php))
Sama seperti pada App portal, kolom menggunakan `IconColumn` dan aksi ubah status disematkan pada `recordActions`:
```php
// Kolom
IconColumn::make('has_hardcopy')
    ->label('Hardcopy')
    ->boolean()
    ->sortable(),

// Aksi Baris (recordActions)
static::toggleHardcopyAction(),
```

Definisi helper action:
```php
private static function toggleHardcopyAction(): Action
{
    return Action::make('toggle_hardcopy')
        ->icon(fn (SptjmSekolah $record): Heroicon => $record->has_hardcopy ? Heroicon::XCircle : Heroicon::CheckCircle)
        ->color('gray')
        ->tooltip(fn (SptjmSekolah $record): string => $record->has_hardcopy ? 'Tandai Hardcopy Belum Diterima' : 'Tandai Hardcopy Sudah Diterima')
        ->action(function (SptjmSekolah $record): void {
            $record->update(['has_hardcopy' => ! $record->has_hardcopy]);

            Notification::make()
                ->title($record->has_hardcopy
                    ? "Hardcopy {$record->sekolah_nama} ditandai Ada"
                    : "Hardcopy {$record->sekolah_nama} ditandai Tidak Ada"
                )
                ->success()
                ->send();
        });
}
```

---

## 5. Pembaruan Pest Tests

1. Buka [SptjmDokumenDinasTest.php](file:///C:/laragonx/www/ppg26/tests/Feature/SptjmDokumenDinasTest.php).
2. Tambahkan kolom `has_hardcopy` di bagian skema SQLite in-memory pada `beforeEach`.
3. Tulis pengujian fitur untuk memvalidasi skenario role upload:
   * Tes upload oleh user `kgtk` → otomatis set `has_hardcopy` jadi `true`.
   * Tes upload oleh user `member` → otomatis set `has_hardcopy` jadi `false`.
   * Tes hak akses toggle → user dengan role `admin` atau `kgtk` bisa mengubah `has_hardcopy`, user role `member` dinonaktifkan.
