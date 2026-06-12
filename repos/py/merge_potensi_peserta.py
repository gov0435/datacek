import json
import csv
from collections import Counter


def extract_ptk_info(record):
    """Ekstrak flat PTK & sekolah dari nested structure."""
    ptk = record.get('ptk', {}) or {}
    sekolah = ptk.get('ptk_sekolah', {}).get('sekolah', {}) if ptk.get('ptk_sekolah') else {}

    jenjang_obj = sekolah.get('m_jenjang', {}) or {}
    kota_obj = sekolah.get('m_kota', {}) or {}
    propinsi_obj = sekolah.get('m_propinsi', {}) or {}

    return {
        'nama': ptk.get('nama'),
        'nuptk': ptk.get('nuptk'),
        'no_hp': ptk.get('no_hp'),
        'no_ukg': ptk.get('no_ukg'),
        'is_guru_dapodik': ptk.get('is_guru_dapodik'),
        'sekolah_nama': sekolah.get('nama'),
        'sekolah_npsn': sekolah.get('npsn'),
        'sekolah_jenjang': jenjang_obj.get('singkat') if jenjang_obj else None,
        'sekolah_kota': kota_obj.get('keterangan') if kota_obj else None,
        'sekolah_propinsi': propinsi_obj.get('keterangan') if propinsi_obj else None,
    }


def load_json(path):
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


# 1. Load ------------------------------------------------------------------
potensi_list = load_json('potensi_keberminatan.json')
peserta_list = load_json('peserta_berminat.json')

# 2. Build maps ------------------------------------------------------------
potensi_map = {p['ptk_id']: p for p in potensi_list if p.get('ptk_id')}
peserta_map = {p['ptk_id']: p for p in peserta_list if p.get('ptk_id')}

all_ptk_ids = sorted(set(potensi_map.keys()) | set(peserta_map.keys()))

# 3. Merge -----------------------------------------------------------------
rows = []
for pid in all_ptk_ids:
    pot = potensi_map.get(pid)
    pes = peserta_map.get(pid)

    # Fallback: peserta -> potensi untuk identitas & sekolah
    ptk_info = extract_ptk_info(pes) if pes else (extract_ptk_info(pot) if pot else {})

    row = {'ptk_id': pid}
    row.update(ptk_info)

    # --- Sumber data ---
    if pot and pes:
        row['source'] = 'keduanya'
    elif pot:
        row['source'] = 'hanya_potensi'
    else:
        row['source'] = 'hanya_peserta'

    # --- Potensi-specific ---
    if pot:
        row['potensi_ppgdj_keberminatan_id'] = pot.get('ppgdj_keberminatan_id')
        row['potensi_tahun'] = pot.get('tahun')
        row['potensi_gelombang'] = pot.get('gelombang')
        row['potensi_status'] = pot.get('status')
        row['potensi_alasan'] = pot.get('alasan')
        row['potensi_waktu'] = pot.get('waktu')
        row['potensi_instansi_id'] = pot.get('instansi_id')
        row['potensi_akun_id'] = pot.get('akun_id')
        row['potensi_created_at'] = pot.get('created_at')
        row['potensi_updated_at'] = pot.get('updated_at')
    else:
        for k in ['potensi_ppgdj_keberminatan_id', 'potensi_tahun', 'potensi_gelombang',
                  'potensi_status', 'potensi_alasan', 'potensi_waktu',
                  'potensi_instansi_id', 'potensi_akun_id',
                  'potensi_created_at', 'potensi_updated_at']:
            row[k] = None

    # --- Peserta-specific ---
    if pes:
        row['peserta_id'] = pes.get('ppgdj_kandidat_status_id')
        row['peserta_ppgdj_mhs_id'] = pes.get('ppgdj_mhs_id')
        row['peserta_tahun'] = pes.get('tahun')
        row['peserta_gelombang'] = pes.get('gelombang')
        row['peserta_nik'] = pes.get('nik')
        row['peserta_nik_status'] = pes.get('nik_status')
        row['peserta_pusdatin_status'] = pes.get('pusdatin_status')
        row['peserta_bagren_status'] = pes.get('bagren_status')
        row['peserta_bagren_error'] = pes.get('bagren_error')
        row['peserta_layak_daftar'] = pes.get('layak_daftar')
        row['peserta_keberminatan_status'] = pes.get('keberminatan_status')
        row['peserta_keberminatan_waktu'] = pes.get('keberminatan_waktu')
        row['peserta_keberminatan_alasan'] = pes.get('keberminatan_alasan')
        row['peserta_created_at'] = pes.get('created_at')
        row['peserta_updated_at'] = pes.get('updated_at')
    else:
        for k in ['peserta_id', 'peserta_ppgdj_mhs_id', 'peserta_tahun', 'peserta_gelombang',
                  'peserta_nik', 'peserta_nik_status', 'peserta_pusdatin_status',
                  'peserta_bagren_status', 'peserta_bagren_error', 'peserta_layak_daftar',
                  'peserta_keberminatan_status', 'peserta_keberminatan_waktu',
                  'peserta_keberminatan_alasan',
                  'peserta_created_at', 'peserta_updated_at']:
            row[k] = None

    rows.append(row)

# 4. Write CSV -------------------------------------------------------------
fieldnames = [
    'ptk_id', 'source',
    'nama', 'nuptk', 'no_ukg', 'no_hp', 'is_guru_dapodik',
    'sekolah_nama', 'sekolah_npsn', 'sekolah_jenjang',
    'sekolah_kota', 'sekolah_propinsi',
    'potensi_ppgdj_keberminatan_id', 'potensi_tahun', 'potensi_gelombang',
    'potensi_status', 'potensi_alasan', 'potensi_waktu',
    'potensi_instansi_id', 'potensi_akun_id',
    'potensi_created_at', 'potensi_updated_at',
    'peserta_id', 'peserta_ppgdj_mhs_id', 'peserta_tahun', 'peserta_gelombang',
    'peserta_nik', 'peserta_nik_status', 'peserta_pusdatin_status',
    'peserta_bagren_status', 'peserta_bagren_error', 'peserta_layak_daftar',
    'peserta_keberminatan_status', 'peserta_keberminatan_waktu',
    'peserta_keberminatan_alasan',
    'peserta_created_at', 'peserta_updated_at',
]

output_path = 'merged_potensi_peserta.csv'
with open(output_path, 'w', newline='', encoding='utf-8-sig') as f:
    writer = csv.DictWriter(f, fieldnames=fieldnames)
    writer.writeheader()
    writer.writerows(rows)

# 5. Summary ---------------------------------------------------------------
sources = Counter(r['source'] for r in rows)

print('=' * 60)
print('MERGE SUMMARY')
print('=' * 60)
print(f'Total rows written: {len(rows)}')
print(f'  - hanya_potensi : {sources["hanya_potensi"]}')
print(f'  - hanya_peserta : {sources["hanya_peserta"]}')
print(f'  - keduanya      : {sources["keduanya"]}')
print(f'Output file: {output_path}')
print('=' * 60)

# Ringkasan status per source
print()
print('--- STATUS POTENSI (hanya_potensi) ---')
only_potensi_status = Counter(r['potensi_status'] for r in rows if r['source'] == 'hanya_potensi')
for s, c in only_potensi_status.most_common():
    print(f'  {s or "(null)":25s}: {c}')

print()
print('--- STATUS POTENSI (keduanya) ---')
both_potensi_status = Counter(r['potensi_status'] for r in rows if r['source'] == 'keduanya')
for s, c in both_potensi_status.most_common():
    print(f'  {s or "(null)":25s}: {c}')

print()
print('--- STATUS PESERTA (keduanya) ---')
both_peserta_status = Counter(r['peserta_keberminatan_status'] for r in rows if r['source'] == 'keduanya')
for s, c in both_peserta_status.most_common():
    print(f'  {s or "(null)":25s}: {c}')

print()
print('--- STATUS PESERTA (hanya_peserta / 10 anomali) ---')
only_peserta_status = Counter(r['peserta_keberminatan_status'] for r in rows if r['source'] == 'hanya_peserta')
for s, c in only_peserta_status.most_common():
    print(f'  {s or "(null)":25s}: {c}')

print()
print('--- LAYAK DAFTAR (hanya_peserta / 10 anomali) ---')
only_peserta_layak = Counter(r['peserta_layak_daftar'] for r in rows if r['source'] == 'hanya_peserta')
for s, c in only_peserta_layak.most_common():
    print(f'  {s or "(null)":25s}: {c}')

print()
print('Done.')
