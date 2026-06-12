# Analisis Data & Rekomendasi Merge

## Ringkasan Eksekutif

Terdapat **3 sumber data** JSON yang mengandung informasi terkait PPG Daljab tahun **2026 gelombang 2** untuk wilayah Provinsi Gorontalo. Analisis ini bertujuan untuk mengidentifikasi kolom-kolom penting, memahami hubungan antar dataset, dan merancang strategi penggabungan data yang optimal.

| Dataset | Jumlah Record | Karakteristik |
|---------|--------------|---------------|
| `potensi_keberminatan.json` | 1.662 | Data respon keberminatan dari PTK (Berminat/Tidak Berminat) |
| `peserta_berminat.json` | 536 | Data kandidat dengan tracking proses administrasi (nik, bagren, pusdatin) |
| `verval_profil.json` | 223 | Data profil lengkap yang sudah diverifikasi (verval) |

**Total PTK unik**: 1.672 individu  
**Overlap PTK_ID**:
- Potensi & Peserta = **526**
- Potensi & Verval = **222**
- Peserta & Verval = **223**
- Ketiga dataset = **222**

---

## 1. Struktur & Kolom Penting per Dataset

### 1.1 `potensi_keberminatan.json`

Dataset ini mencatat status keberminatan PTK terhadap program PPG. Setiap record memiliki objek `ptk` yang bersarang hingga informasi sekolah.

| Kolom | Tipe | Keterangan | Penting? |
|-------|------|------------|----------|
| `ptk_id` | string | **Primary Key / Join Key** - ID unik PTK | **WAJIB** |
| `tahun` | int | Tahun PPG (2026) | Wajib |
| `gelombang` | int | Gelombang (2) | Wajib |
| `status` | string | `Berminat` / `Tidak Berminat` / `Sedang PPG` / `Sudah Berserdik` / `None` | **WAJIB** |
| `alasan` | string | Alasan jika Tidak Berminat | **WAJIB** |
| `waktu` | datetime | Waktu pengisian keberminatan | Wajib |
| `instansi_id` | int | ID instansi penginput (bisa null) | Penting |
| `akun_id` | string | ID akun penginput | Pendukung |
| `ptk.nama` | string | Nama lengkap | **WAJIB** |
| `ptk.nuptk` | string | NUPTK | Wajib |
| `ptk.no_hp` | string | Nomor HP | Penting |
| `ptk.no_ukg` | string | Nomor UKG | Wajib |
| `ptk.is_guru_dapodik` | bool | Validasi guru Dapodik | Penting |
| `ptk.ptk_sekolah.sekolah.nama` | string | Nama sekolah | **WAJIB** |
| `ptk.ptk_sekolah.sekolah.npsn` | string | NPSN sekolah | Wajib |
| `ptk.ptk_sekolah.sekolah.m_jenjang.singkat` | string | Jenjang (PAUD/SD/SMP/SMA/SMK/SLB) | **WAJIB** |
| `ptk.ptk_sekolah.sekolah.m_kota.keterangan` | string | Kabupaten/Kota | **WAJIB** |
| `ptk.ptk_sekolah.sekolah.m_propinsi.keterangan` | string | Provinsi | Wajib |

> **Catatan**: Semua record memiliki `ppgdj_mhs_id` = `null`, sehingga kolom ini **tidak bisa** digunakan sebagai join key ke dataset lain.

**Distribusi Status Keberminatan**:

| Status | Jumlah |
|--------|--------|
| Berminat | 925 |
| Tidak Berminat | 525 |
| (kosong/None) | 85 |
| Sudah Berserdik | 66 |
| Sedang PPG | 61 |

**Distribusi Jenjang Sekolah**:

| Jenjang | Jumlah |
|---------|--------|
| PAUD | 768 |
| SD | 555 |
| SMP | 230 |
| SMK | 66 |
| SMA | 27 |
| SLB | 13 |
| Lainnya | 3 |

**Distribusi Kota/Kabupaten (Top 6)**:

| Kota/Kab | Jumlah |
|----------|--------|
| Kab. Gorontalo | 461 |
| Kab. Bonebolango | 279 |
| Kab. Boalemo | 262 |
| Kota Gorontalo | 240 |
| Kab. Pohuwato | 219 |
| Kab. Gorontalo Utara | 201 |

---

### 1.2 `peserta_berminat.json`

Dataset ini berisi data kandidat yang proses administrasinya sudah lebih jauh, meliputi validasi NIK (`pusdatin`) dan pengecekan kelayakan (`bagren`).

| Kolom | Tipe | Keterangan | Penting? |
|-------|------|------------|----------|
| `ptk_id` | string | **Primary Key / Join Key** | **WAJIB** |
| `ppgdj_mhs_id` | int | ID Mahasiswa PPG (join ke verval) | **WAJIB** |
| `tahun` | int | 2026 | Wajib |
| `gelombang` | int | 2 | Wajib |
| `nik` | string | NIK dari PTK | **WAJIB** |
| `nik_status` | string | Validasi NIK (`Valid` / invalid) | **WAJIB** |
| `pusdatin_status` | string | Status cek Pusdatin (`Berhasil`/null) | Penting |
| `bagren_status` | string | Status cek Bagren (`Berhasil`/`Antri`/`Gagal`) | **WAJIB** |
| `bagren_error` | string | Pesan error jika gagal | Pendukung |
| `layak_daftar` | string | `Layak Daftar` / `Tidak Layak Daftar` | **WAJIB** |
| `keberminatan_status` | string | Status keberminatan (mirroring potensi) | **WAJIB** |
| `keberminatan_waktu` | datetime | Waktu konfirmasi keberminatan | Wajib |
| `keberminatan_response` | object | Respons API saat update keberminatan | Pendukung |
| `created_at` | datetime | Waktu record dibuat | Wajib |
| `ptk.nama` | string | Nama lengkap | Wajib |
| `ptk.nuptk` | string | NUPTK | Wajib |
| `ptk.no_ukg` | string | Nomor UKG | Wajib |
| `ptk.ptk_sekolah.sekolah.*` | object | Info sekolah (sama struktur dengan potensi) | Wajib |

**Distribusi Status Bagren**:

| Status | Jumlah |
|--------|--------|
| Berhasil | 232 |
| Antri | 226 |
| Gagal | 63 |
| (kosong) | 15 |

**Distribusi Layak Daftar**:

| Status | Jumlah |
|--------|--------|
| Layak Daftar | 458 |
| Tidak Layak Daftar | 40 |
| (kosong) | 38 |

**Distribusi Keberminatan**:

| Status | Jumlah |
|--------|--------|
| Berminat | 493 |
| Tidak Berminat | 39 |
| Sudah Berserdik | 4 |

---

### 1.3 `verval_profil.json`

Dataset ini merupakan data profil terlengkap yang sudah melalui proses verifikasi dan validasi (verval). Ini adalah dataset dengan granularitas tertinggi.

| Kolom | Tipe | Keterangan | Penting? |
|-------|------|------------|----------|
| `ptk_id` | string | **Primary Key / Join Key** | **WAJIB** |
| `ppgdj_mhs_id` | int | ID Mahasiswa PPG (join ke peserta) | **WAJIB** |
| `tahun` | int | 2026 | Wajib |
| `gelombang` | int | 2 | Wajib |
| `nama` | string | Nama lengkap | **WAJIB** |
| `nik` | string | NIK | **WAJIB** |
| `nuptk` | string | NUPTK | Wajib |
| `no_ukg` | string | Nomor UKG | Wajib |
| `no_hp` | string | Nomor HP | Penting |
| `kelamin` | string | `L` / `P` | Wajib |
| `tmp_lahir` | string | Tempat lahir | Wajib |
| `tgl_lahir` | date | Tanggal lahir | Wajib |
| `k_pegawai` / `m_pegawai.keterangan` | string | Status kepegawaian | **WAJIB** |
| `nip` | string | NIP (jika PNS/PPPK) | Penting |
| `k_golongan` | int | Golongan | Pendukung |
| `tmt_guru` | date | TMT menjadi guru | Wajib |
| `jabatan` | string | Jabatan | Wajib |
| `k_jabatan_guru` | int | Kode jabatan guru | Pendukung |
| `sekolah_id` | int | ID sekolah | Wajib |
| `sekolah` | string | Nama sekolah | **WAJIB** |
| `npsn` | string | NPSN sekolah | Wajib |
| `k_jenjang` / `m_jenjang.singkat` | int/string | Jenjang sekolah | **WAJIB** |
| `k_propinsi` / `m_propinsi.keterangan` | int/string | Provinsi | Wajib |
| `k_kota` / `m_kota.keterangan` | int/string | Kabupaten/Kota | **WAJIB** |
| `kecamatan` | string | Kecamatan | Wajib |
| `alamat` | string | Alamat rumah | Penting |
| `k_kualifikasi` / `m_kualifikasi.singkat` | int/string | Kualifikasi pendidikan (S1/DIV/etc) | **WAJIB** |
| `fakultas_s1` | string | Fakultas S1 | Pendukung |
| `jurusan_s1` | string | Jurusan S1 | Pendukung |
| `pt_s1` | string | Perguruan tinggi S1 | Penting |
| `pt_ijazah` | string | PT ijazah | Pendukung |
| `k_jurusan_ppg` / `m_jurusan_ppg.keterangan` | int/string | Jurusan PPG | **WAJIB** |
| `perti_ppg` | string | Perguruan tinggi PPG | **WAJIB** |
| `prodi_ppg` | string | Program studi PPG | **WAJIB** |
| `k_mapel_ppg` / `m_mapel_ppg.keterangan` | int/string | Mapel PPG | Wajib |
| `gelar_depan` | string | Gelar depan | Pendukung |
| `gelar_belakang` | string | Gelar belakang | Pendukung |
| `email_belajar` | string | Email belajar.id | Penting |
| `is_lapor` | string/enum | Sudah lapor? (`0`/`1`) | **WAJIB** |
| `is_undur` | string/enum | Mengundurkan diri? (`0`/`1`) | **WAJIB** |
| `alasan` | string | Alasan mundur (jika ada) | Penting |
| `is_kandidat` | int | Status kandidat | Wajib |
| `is_peserta` | string/enum | Status peserta (`0`/`1`) | **WAJIB** |
| `k_verval_ppg` / `m_verval_ppg.keterangan` | int/string | Status verval | **WAJIB** |
| `wkt_ajuan` | datetime | Waktu pengajuan | Wajib |
| `wkt_verval` | datetime | Waktu verval | Wajib |
| `alasan_verval` | string | Alasan hasil verval | Pendukung |
| `is_lengkap_pks` | string/enum | Kelengkapan PKS | Penting |
| `is_lengkap_laporan` | string/enum | Kelengkapan laporan | Penting |
| `is_epks` | string/enum | Status e-PKS | Penting |
| `status_syarat` | array[] | Daftar syarat dan status kecukupannya | **WAJIB** |
| `data.keterangan` | string | Ringkasan kelayakan dari API Dapodik | **WAJIB** |
| `data.usia_per_1_jan_2025` | int | Usia per 1 Januari 2025 | Penting |
| `data.kode_bidangstudi_ppg` | array[] | Kode bidang studi PPG | Pendukung |
| `data.prodi_s1` | string | Prodi S1 dari Dapodik | Penting |
| `bank` fields (`k_bank`, `rekening_nama`, `rekening_nomor`, `rekening_cabang`) | mixed | Data rekening | Pendukung |
| `sekolah_dapodik.*` | object | Data sekolah lengkap dari Dapodik | Pendukung |
| `ppgdj_kandidats` | array[] | Data kandidat (skor, status seleksi) | **WAJIB** |
| `is_cadangan` | string/enum | Status cadangan | Wajib |
| `is_plpg` | string/enum | Status PLPG | Wajib |
| `is_kasek` | bool | Apakah Kepala Sekolah? | Penting |

**Distribusi Status Verval**:

| Status | Jumlah |
|--------|--------|
| Ajuan Disetujui (k_verval_ppg=6) | 201 |
| Pendaftar Sudah Ajuan (k_verval_ppg=2) | 22 |

**Distribusi Status Kepegawaian**:

| Status | Jumlah |
|--------|--------|
| Guru Honor Sekolah | 109 |
| GTY/PTY | 32 |
| Honor Daerah TK.II Kab/Kota | 27 |
| Tenaga Honor Sekolah | 16 |
| PPPK Paruh Waktu | 14 |
| PPPK | 11 |
| PNS | 10 |
| Honor Daerah TK.I Provinsi | 4 |

> **Catatan Penting**: Semua record di dataset ini memiliki `is_peserta = "0"` dan `jenis = "kandidat"`, artinya data ini adalah calon peserta yang sudah terverval, namun belum ditetapkan sebagai peserta tetap.

---

## 2. Strategi Merge (Penggabungan Data)

### 2.1 Join Key

Berdasarkan analisis, terdapat **dua kunci** yang dapat digunakan untuk menggabungkan dataset:

1. **`ptk_id`** (SIMPKB ID / Nomor UKG): 
   - Tersedia di **ketiga dataset**.
   - merupakan kunci utama yang paling universal.
   - Tipe: string.

2. **`ppgdj_mhs_id`**:
   - Hanya tersedia di **`peserta_berminat`** dan **`verval_profil`**.
   - **Tidak tersedia** di `potensi_keberminatan` (semua null).
   - Digunakan untuk join kedua dataset backend,
   - Tipe: integer.

**Rekomendasi Join Strategy**:

```
┌─────────────────────────┐
│  potensi_keberminatan   │  ← Base layer (1.662 record)
│      (ptk_id)           │
└──────────┬──────────────┘
           │ LEFT JOIN
           ▼
┌─────────────────────────┐
│   peserta_berminat      │  ← Layer administrasi (526 overlap)
│   (ptk_id / ppgdj_mhs_id)│
└──────────┬──────────────┘
           │ LEFT JOIN
           ▼
┌─────────────────────────┐
│     verval_profil       │  ← Layer profil lengkap (222 overlap)
│   (ptk_id / ppgdj_mhs_id)│
└─────────────────────────┘
```

### 2.2 Alasan Pemilihan LEFT JOIN

- `potensi_keberminatan` memiliki cakupan PTK terluas (1.662). Sebagian besar PTK sudah mengisi data keberminatan.
- Hanya **526 PTK** (31.6%) yang masuk ke tahap `peserta_berminat`.
- Hanya **222 PTK** (13.3%) yang masuk ke tahap `verval_profil`.
- Sebagian besar PTK (1.136 orang) hanya ada di data potensi dan belum berproses lebih jauh.

---

## 3. Skema Hasil Merge yang Direkomendasikan

### 3.1 Tabel Master: `merged_ppg_data`

Berikut adalah kolom-kolom penting yang harus ada setelah merge:

#### A. Identitas PTK (dari potensi → peserta → verval)

| Kolom Output | Sumber Prioritas | Keterangan |
|--------------|-----------------|------------|
| `ptk_id` | Semua (join key) | ID PTK |
| `nama` | verval > peserta > potensi | Nama lengkap |
| `nik` | verval > peserta | NIK |
| `nuptk` | verval > peserta > potensi | NUPTK |
| `no_ukg` | verval > peserta > potensi | Nomor UKG |
| `no_hp` | verval > peserta > potensi | No HP |
| `kelamin` | verval | L/P |
| `tmp_lahir` | verval | Tempat lahir |
| `tgl_lahir` | verval | Tanggal lahir |
| `email_belajar` | verval | Email @belajar.id |

#### B. Kepegawaian & Sekolah

| Kolom Output | Sumber Prioritas | Keterangan |
|--------------|-----------------|------------|
| `status_kepegawaian` | verval.`m_pegawai.keterangan` | Guru Honor / PNS / PPPK / GTY / PTY |
| `nip` | verval | NIP (jika ASN) |
| `jabatan` | verval | Guru Kelas / Guru Mapel / Kepala Sekolah |
| `tmt_guru` | verval | TMT awal jadi guru |
| `sekolah_id` | verval > potensi/peserta | ID sekolah |
| `sekolah_nama` | verval.`sekolah` > nested | Nama sekolah |
| `npsn` | verval > nested | NPSN |
| `jenjang` | verval.`m_jenjang.singkat` > nested | PAUD / SD / SMP / SMA / SMK / SLB |
| `propinsi` | verval.`m_propinsi.keterangan` > nested | Gorontalo |
| `kota` | verval.`m_kota.keterangan` > nested | Kab. xxx / Kota xxx |
| `kecamatan` | verval | Kecamatan |

#### C. Riwayat Pendidikan

| Kolom Output | Sumber Prioritas | Keterangan |
|--------------|-----------------|------------|
| `kualifikasi` | verval.`m_kualifikasi.singkat` | S1 / DII / dll |
| `prodi_s1` | verval.`data.prodi_s1` | Jurusan S1 dari Dapodik |
| `pt_s1` | verval | Perguruan tinggi S1 |
| `prodi_ppg` | verval | Prodi PPG yang dipilih |
| `jurusan_ppg` | verval.`m_jurusan_ppg.keterangan` | Jurusan PPG |
| `perti_ppg` | verval | PT penyelenggara PPG |
| `mapel_ppg` | verval.`m_mapel_ppg.keterangan` | Mapel PPG |

#### D. Status Keberminatan & Proses Administrasi

| Kolom Output | Sumber Prioritas | Keterangan |
|--------------|-----------------|------------|
| `potensi_status` | potensi.`status` | Status keberminatan awal |
| `potensi_alasan` | potensi.`alasan` | Alasan tidak berminat |
| `potensi_waktu` | potensi.`waktu` | Waktu isi potensi |
| `peserta_keberminatan_status` | peserta.`keberminatan_status` | Status keberminatan (update) |
| `peserta_keberminatan_waktu` | peserta.`keberminatan_waktu` | Waktu update |
| `nik_status` | peserta | Valid / Invalid |
| `pusdatin_status` | peserta | Status verifikasi Pusdatin |
| `bagren_status` | peserta | Berhasil / Antri / Gagal |
| `layak_daftar` | peserta | Layak / Tidak Layak |
| `bagren_error` | peserta | Pesan error (jika ada) |

#### E. Status Verval & Penerimaan

| Kolom Output | Sumber Prioritas | Keterangan |
|--------------|-----------------|------------|
| `ppgdj_mhs_id` | verval > peserta | ID mahasiswa PPG |
| `is_lapor` | verval | Sudah lapor? (1/0) |
| `is_undur` | verval | Mengundur diri? (1/0) |
| `alasan_undur` | verval.`alasan` | Alasan mundur |
| `k_verval_ppg` | verval.`m_verval_ppg.keterangan` | Ajuan Disetujui / Sudah Ajuan |
| `wkt_ajuan` | verval | Waktu ajuan |
| `wkt_verval` | verval | Waktu diverval |
| `is_kandidat` | verval | Status kandidat |
| `is_peserta` | verval | Status peserta (sampai saat ini semua 0) |
| `is_cadangan` | verval | Cadangan? |
| `is_plpg` | verval | PLPG? |
| `is_kasek` | verval | Kepala Sekolah? |
| `is_lengkap_pks` | verval | PKS lengkap? |
| `is_lengkap_laporan` | verval | Laporan lengkap? |
| `is_epks` | verval | e-PKS? |
| `status_selak` | verval | Status seleksi akademik |
| `skor_total_final` | verval.`ppgdj_kandidats[].skor_total_final` | Skor seleksi |
| `is_lulus` | verval.`ppgdj_kandidats[].is_lulus` | Lulus seleksi? |

#### F. Kelengkapan Syarat (dari `status_syarat` array)

Array `status_syarat` di `verval_profil` berisi 14 item syarat dengan format `[nama_syarat, boolean_status, keterangan_optional]`. Kolom-kolom turunan yang penting:

| Kolom Output | Sumber | Keterangan |
|--------------|--------|------------|
| `syarat_simplkb_id` | status_syarat[0] | SIMPKB ID terdaftar? |
| `syarat_nik` | status_syarat[1] | NIK valid? |
| `syarat_status_guru` | status_syarat[2] | Berstatus guru/ks? |
| `syarat_usia` | status_syarat[3] | < 60 tahun? |
| `syarat_satminkal` | status_syarat[4] | 1 induk satminkal? |
| `syarat_belum_serdik` | status_syarat[5] | Belum punya serdik? |
| `syarat_bukan_peserta_lama` | status_syarat[6] | Bukan peserta PPG lama? |
| `syarat_mengajar_2024_2025` | status_syarat[7] | Aktif mengajar TA 2024/2025? |
| `syarat_mengajar_2023_2024` | status_syarat[8] | Aktif mengajar TA 2023/2024 penuh? |
| `syarat_mengajar_sebelum_2023` | status_syarat[9] | Aktif mengajar sebelum 2023/2024? |
| `syarat_terdaftar_2023_2024` | status_syarat[10] | Terdaftar guru TA 2023/2024? |
| `syarat_bukan_seladm_2024` | status_syarat[11] | Bukan seladm GT 2024? |
| `syarat_bukan_seladm_2025` | status_syarat[12] | Bukan seladm GT 2025? |
| `syarat_bukan_konfirmasi_2025` | status_syarat[13] | Bukan peserta konfirmasi 2025? |

#### G. Metadata & Tracking

| Kolom Output | Sumber | Keterangan |
|--------------|--------|------------|
| `tahun` | Semua | 2026 |
| `gelombang` | Semua | 2 |
| `instansi_id` | potensi | ID instansi penginput |
| `akun_id` | potensi | ID akun penginput |
| `created_at_potensi` | potensi | Waktu record potensi dibuat |
| `created_at_peserta` | peserta | Waktu record peserta dibuat |
| `updated_at_peserta` | peserta | Last update |
| `created_at_verval` | verval | Waktu record verval dibuat |
| `updated_at_verval` | verval | Last update |

---

## 4. Insight & Temuan Penting

### 4.1 Data "Hanya Potensi" (Tidak Berproses)

- **1.136 PTK** (68.2%) hanya memiliki data di `potensi_keberminatan` dan tidak masuk ke dataset peserta ataupun verval.
- Mayoritas dari mereka yang **Tidak Berminat** (525 orang) memiliki alasan konkret:
  - Bukan guru (tenaga administrasi / operator / tendik)
  - Sudah pensiun / akan pensiun
  - Sudah terangkat PPPK di instansi non-guru
  - Sudah berserdik / sedang PPG
  - Belum S1
- Ini adalah data "drop-off" yang berguna untuk analisis target sasaran di periode berikutnya.

### 4.2 Inkonstistensi Status

Terdapat perbedaan status `keberminatan` antara `potensi_keberminatan` dan `peserta_berminat`:

- **Potensi**: 925 Berminat, 525 Tidak Berminat, 85 kosong, 66 Sudah Berserdik, 61 Sedang PPG.
- **Peserta**: 493 Berminat, 39 Tidak Berminat, 4 Sudah Berserdik.

Perbedaan ini terjadi karena:
1. Update status dilakukan pada waktu yang berbeda.
2. Mekanisme update backend (`peserta`) vs frontend (`potensi`) bisa berbeda.
3. Status `Sudah Berserdik` dan `Sedang PPG` di potensi kemungkinan diinput oleh admin/operator, sedangkan di peserta lebih terintegrasi dengan API pusdatin/bagren.

**Rekomendasi**: Gunakan kolom `peserta_keberminatan_status` sebagai "single source of truth" untuk status akhir, karena dataset peserta memiliki tracking log (`keberminatan_waktu`, `keberminatan_response`).

### 4.3 Error pada Proses Bagren

Di dataset `peserta_berminat`, terdapat **63 record Gagal** dan **226 record Antri** pada proses `bagren`.

Contoh error yang terjadi:
- `cURL error 7: Couldn't connect to server` (koneksi timeout ke API Bagren)
- `Failed to open stream: Too many open files` (server-side resource limit)

Ini bukan kesalahan data peserta, melainkan masalah teknis server/backend. Record dengan error ini perlu direproses.

### 4.4 Data Verval vs Realitas Peserta

- Jumlah record di `verval_profil` (223) hampir sama dengan overlap `peserta & verval` (223), artinya **hampir semua peserta yang masuk ke tahap verval sudah terverval**.
- Semua record verval memiliki `is_peserta = "0"`, yang berarti data ini snapshot sebelum penetapan peserta final.
- 22 record memiliki status verval `Pendaftar Sudah Ajuan` (belum disetujui), sedangkan 201 record sudah `Ajuan Disetujui`.

### 4.5 Distribusi Jenjang yang Signifikan

- **PAUD** mendominasi data potensi (768 orang / 46.2%) dan peserta (220 / 41.0%), namun proporsi SMK dan SMA sangat kecil.
- Ini mengindikasikan bahwa target sasaran PPG Daljab 2026 gelombang 2 di Gorontalo didominasi oleh guru PAUD dan SD.

### 4.6 Sebaran Wilayah

- Data mencakup **6 kabupaten/kota** di Gorontalo.
- `Kab. Gorontalo` memiliki jumlah PTK potensi terbesar (461), diikuti `Kab. Bonebolango` (279).
- Distribusi relatif merata meskipun ada dominasi di Kab. Gorontalo.

---

## 5. Rekomendasi Teknis untuk Implementasi Merge

### 5.1 Langkah-langkah Merge dengan Python/Pandas

```python
import pandas as pd
import json

# 1. Load dan flatten data
with open('potensi_keberminatan.json') as f:
    potensi_raw = json.load(f)
with open('peserta_berminat.json') as f:
    peserta_raw = json.load(f)
with open('verval_profil.json') as f:
    verval_raw = json.load(f)

# 2. Normalisasi nested JSON ke flat DataFrame
df_potensi = pd.json_normalize(potensi_raw, sep='_')
df_peserta = pd.json_normalize(peserta_raw, sep='_')
df_verval = pd.json_normalize(verval_raw, sep='_')

# 3. Standardisasi kolom join
df_potensi['ptk_id'] = df_potensi['ptk_id'].astype(str)
df_peserta['ptk_id'] = df_peserta['ptk_id'].astype(str)
df_verval['ptk_id'] = df_verval['ptk_id'].astype(str)

# 4. Left join: potensi -> peserta -> verval
df_merge = df_potensi.merge(
    df_peserta, on='ptk_id', how='left', suffixes=('', '_peserta')
)
df_merge = df_merge.merge(
    df_verval, on='ptk_id', how='left', suffixes=('', '_verval')
)

# 5. Seleksi kolom penting saja (lihat skema Bagian 3)
# Contoh:
kolom_penting = [
    'ptk_id', 'nama', 'nik', 'nuptk', 'no_ukg', 'no_hp', 'kelamin',
    'status_kepegawaian', 'sekolah_nama', 'npsn', 'jenjang', 'kota',
    'potensi_status', 'peserta_keberminatan_status', 'bagren_status',
    'layak_daftar', 'k_verval_ppg', 'is_peserta', 'prodi_ppg',
    # ... tambahkan sesuai kebutuhan
]
```

### 5.2 Penanganan NULL & Inkonsistensi

| Issue | Solusi |
|-------|--------|
| `ptk_id` ada di potensi tapi tidak di peserta/verval | Biarkan kolom hasil merge bernilai `NaN` / `null` |
| `ppgdj_mhs_id` null di potensi | Isi dari `peserta` atau `verval` setelah join |
| Perbedaan `nama` antar dataset | Prioritaskan `verval`, lalu `peserta`, lalu `potensi` |
| `status` berbeda antara potensi dan peserta | Simpan keduanya dengan suffix berbeda |
| Array `status_syarat` | Flatten ke boolean per item syarat |
| Datetime format tidak seragam | Standardisasi ke ISO 8601 atau YYYY-MM-DD HH:MM:SS |

### 5.3 Validasi Post-Merge

Setelah merge, lakukan validasi berikut:

1. **Cek jumlah baris**: Hasil harus = 1.662 baris (sama dengan potensi, karena LEFT JOIN).
2. **Cek duplicate `ptk_id`**: Pastikan tidak ada duplikat (harus 1.672 unik jika semua dataset digabung, 1.662 jika base=potensi).
3. **Cek NULL di kolom kritikal**: `ptk_id`, `nama`, `status` tidak boleh NULL.
4. **Cross-check status**: Bandingkan `potensi_status` vs `peserta_keberminatan_status` untuk record yang overlap. Jika beda, catat sebagai "status berubah".

---

## 6. Kesimpulan

Dataset `potensi_keberminatan`, `peserta_berminat`, dan `verval_profil` merepresentasikan **tiga tahapan funnel** dalam proses rekrutmen PPG Daljab 2026 Gel. 2:

1. **Potensi** (Top of Funnel): 1.662 PTK mengisi survei keberminatan.
2. **Peserta Berminat** (Middle of Funnel): 536 PTK lolos filtering administrasi awal (NIK, bagren).
3. **Verval Profil** (Bottom of Funnel): 223 PTK melengkapi profil dan terverifikasi.

Kunci penggabungan adalah **`ptk_id`**, dengan **`ppgdj_mhs_id`** sebagai kunci sekunder antara peserta dan verval. Hasil merge akan memberikan gambaran **end-to-end** dari setiap PTK: mulai dari keberminatan awal, proses administrasi, hingga status verval akhir.

---

*Dokumen ini dibuat secara otomatis berdasarkan analisis struktur data JSON.*
