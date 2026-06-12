# Analisis Duplikat di potensi_keberminatan.json

## Duplikat Nama — 3 Kelompok

| Nama | ptk_id 1 | ptk_id 2 | Status |
|------|----------|----------|--------|
| **EWIN OLII** | 201901149365 (Berminat) | 202000492594 (Berminat) | Sama-sama Berminat, tanpa NUPTK |
| **MELIS HASAN** | 202100250557 (Tidak Berminat) | 201901144919 (Berminat) | Beda status |
| **RAHMAWATI YUSUF** | 202400053210 (Berminat, NUPTK=6449779680230092) | 202200236265 (Tidak Berminat, tanpa NUPTK) | Beda status, 1 punya NUPTK |

## Duplikat NUPTK

Tidak ada — semua NUPTK unik.

## Duplikat no_hp — 3 Nomor

| No. HP | ptk_id 1 | ptk_id 2 | Status |
|--------|----------|----------|--------|
| **08** (invalid) | 202400218522 (SINDI DJAKARIA, Sedang PPG) | 202300186743 (SELIS DAI, Berminat) | Data tidak valid |
| **082190406279** | 202400066491 (INDRAWATI S.DAUD, Berminat) | 202400066490 (RIANTI SUDIRMAN, Berminat) | Beda orang, HP sama |
| **085340904976** | 201800238565 (SUMARNI HANAPI, Berminat) | 202000209377 (MERLIN ADAM, Berminat) | Beda orang, HP sama |

## Duplikat NIK di peserta_berminat.json — 1 NIK

| NIK | ptk_id 1 | ptk_id 2 | Nama | Status |
|-----|----------|----------|------|--------|
| **7501040508950001** | 202000501174 | 201901010794 | FIKRIYADI PIPII (sama) | Keduanya Berminat & Layak Daftar |

Semua 536 record punya NIK (0 kosong).

## Statistik no_hp

| Kategori | Jumlah | % |
|----------|--------|---|
| Punya no_hp | 1.063 | 64% |
| no_hp kosong | 599 | 36% |

### Distribusi Status — Punya no_hp

| Status | Jumlah |
|--------|--------|
| Berminat | 648 |
| Tidak Berminat | 245 |
| Sudah Berserdik | 63 |
| Sedang PPG | 59 |
| (null) | 48 |

### Distribusi Status — Tanpa no_hp

| Status | Jumlah |
|--------|--------|
| Tidak Berminat | 280 |
| Berminat | 277 |
| (null) | 37 |
| Sudah Berserdik | 3 |
| Sedang PPG | 2 |

Catatan: 599 record tanpa no_hp, hampir merata antara Berminat (277) dan Tidak Berminat (280).

## Kesimpulan

- Nama duplikat kemungkinan besar **orang berbeda dengan nama sama** (ptk_id berbeda, NUPTK berbeda/kosong).
- Satu kasus mencurigakan: **RAHMAWATI YUSUF** — satu punya NUPTK dan Berminat, satu tanpa NUPTK dan Tidak Berminat. Bisa jadi data ganda dari PTK yang sama.
- Tidak ada duplikat NUPTK yang mengindikasikan kesalahan data sistemik.
- Duplikat no_hp (3 nomor) menunjukkan kemungkinan HP digunakan bersama atau input error.
- Nomor `08` adalah data invalid — panjang tidak wajar.
