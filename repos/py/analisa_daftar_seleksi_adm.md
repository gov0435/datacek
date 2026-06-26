# Analisa: Deteksi Peserta yang Sudah Daftar Seleksi Administrasi

Sumber data: `repos/py/peserta_berminat.json` (560 record) + `repos/py/verval_profil.json` (233 record).

> **Update:** Data ini merupakan analisa historis. Untuk informasi terkini mengenai LPTK dan halaman lapor diri, lihat `database/data/lptk.json`.

## Ringkasan

| Dataset | Jumlah | Keterangan |
|---|---:|---|
| `peserta_berminat` | 560 | Peserta yang sudah klik **Berminat** |
| `verval_profil` | 233 | Peserta yang sudah verval profil (data **tidak realtime**) |
| **Sudah daftar seleksi adm.** | **207** | Layak Daftar + `bagren_status=Berhasil` |

## Latar Belakang: Kenapa `verval_profil` Tidak Cukup

`verval_profil` adalah hasil sinkronisasi dari sistem lokal ke sistem pusat PPG. Prosesnya **tidak realtime** — ada jeda antara data masuk di `peserta_berminat` dan kemunculannya di `verval_profil`.

Buktinya: dari 207 peserta yang indikator `peserta_berminat`-nya sudah "Berhasil", sebanyak **96 (46%) belum muncul** di `verval_profil`.

## Field Penting di `peserta_berminat`

| Field | Nilai | Arti |
|---|---|---|
| `layak_daftar` | `Layak Daftar` / `Tidak Layak Daftar` / `null` | Hasil cek kelayakan; hanya yang **Layak Daftar** yang eligible lanjut |
| `bagren_status` | `Berhasil` | **Sudah push ke sistem PPG pusat** (`118.98.166.135/api/ppg2024/pendaftaran`) |
| `bagren_status` | `Antri` | Masih antrian, belum diproses |
| `bagren_status` | `Gagal` | Gagal push (cURL error / file handle) |
| `bagren_waktu` | timestamp | Waktu selesai proses bagren |
| `bagren_error` | string / `null` | Pesan error jika gagal |
| `keberminatan_status` | `Berminat` | Status minat peserta (semua record = Berminat) |
| `keberminatan_response` | `{"Status":"0","Message":"Data Sudah Ada"}` | Respon API saat insert |
| `pusdatin_status` | `Berhasil` / `null` | Hasil cek ke Pusdatin (validasi NIK) |

## Indikator "Sudah Daftar Seleksi Administrasi"

Kombinasi **dua field**:

```php
$sudahDaftar = $b['layak_daftar'] === 'Layak Daftar'
            && $b['bagren_status'] === 'Berhasil';
```

### Kenapa Harus Dua-Duanya?

- `layak_daftar = "Layak Daftar"` saja → ada 481 peserta, tapi belum tentu sudah push
- `bagren_status = "Berhasil"` saja → ada 235 peserta, tapi 28 di antaranya **Tidak Layak Daftar** (anomali/data lama)
- **Irisan keduanya** = 207 peserta → peserta **eligible DAN sudah sukses push** ke sistem PPG pusat

## Distribusi Silang `layak_daftar` × `bagren_status`

| `layak_daftar` | `bagren_status` | Jumlah | Kesimpulan |
|---|---|---:|---|
| Layak Daftar | Berhasil | **207** | **Sudah daftar** |
| Layak Daftar | Antri | 237 | Antrian, belum pasti |
| Layak Daftar | Gagal | 37 | Gagal, perlu retry |
| Tidak Layak Daftar | Berhasil | 28 | Anomali / data lama |
| Tidak Layak Daftar | Antri | 3 | Tidak eligible |
| Tidak Layak Daftar | Gagal | 10 | Tidak eligible |
| `null` | (bermacam) | 38 | Belum diproses |

## Verifikasi Silang dengan `verval_profil`

Dari 207 peserta "Layak + Berhasil":

| Status di verval | Jumlah | Catatan |
|---|---:|---|
| Ada di verval_profil | 111 | Sudah sinkron |
| Belum ada di verval_profil | 96 | **Lagging** — padahal `bagren` sudah sukses |

Ini mengkonfirmasi bahwa `verval_profil` itu **tidak bisa dipakai sendiri** sebagai sumber kebenaran.

`verval_profil` (233 record) juga berisi peserta `bagren=Antri` (116) dan `bagren=Gagal` (6). Ini sisi lain dari lag: data bisa muncul duluan di `verval_profil` saat status masih `Antri`, atau justru `Gagal` (sinkron manual/admin).

## Field yang **TIDAK Bisa** Dipakai sebagai Indikator

Di file `peserta_berminat` ini, field berikut **semua `null`** untuk 560 record, jadi tidak relevan:

- `keberminatan_tahun_ppg`
- `keberminatan_no_serdik`
- `keberminatan_nim`
- `keberminatan_alasan`
- `push_waktu`, `push_response`

`keberminatan_response` pun **tidak reliable** sebagai indikator tunggal: dari 309 record dengan `"Data Sudah Ada"`, hanya 198 (64%) yang memang sudah verval.

## Referensi: Field di `verval_profil` untuk Cross-Check

Kalau punya akses ke `verval_profil`, tanda pasti sudah verval:

- `k_verval_ppg` = `2` (Diajukan) atau `6` (Disetujui)
- `m_verval_ppg.keterangan` terisi
- `status_syarat` array tidak kosong
- Field profil (`NIP`, `NPWP`, `rekening_nomor`, `email_belajar`, dll) terisi
- `ppgdj_kandidats` array berisi minimal 1 item

**Foreign key** untuk lookup silang: `ppgdj_mhs_id` atau `ptk_id` (ada di kedua JSON).

## Contoh Kode (PHP)

```php
$berminat = json_decode(file_get_contents('repos/py/peserta_berminat.json'), true);

$sudahDaftar = array_filter($berminat, fn ($b) =>
    $b['layak_daftar'] === 'Layak Daftar'
    && $b['bagren_status'] === 'Berhasil'
);

echo 'Sudah daftar seleksi adm.: ' . count($sudahDaftar) . PHP_EOL;
```

## Kesimpulan

1. **`bagren_status = "Berhasil"`** adalah indikator paling kuat bahwa peserta sudah push ke sistem PPG pusat.
2. **Filter tambahan `layak_daftar = "Layak Daftar"`** membuang anomali (28 record yang Berhasil tapi Tidak Layak).
3. **207 peserta** dari 560 = sudah daftar seleksi administrasi.
4. `verval_profil` jangan dipakai sebagai sumber tunggal — lag bisa sampai 96 record.
