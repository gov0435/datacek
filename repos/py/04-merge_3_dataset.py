import json
import csv
import os
from collections import Counter


BASE_DIR = os.path.dirname(os.path.abspath(__file__))


def extract_ptk_fallback(peserta, potensi, verval):
    """Ekstrak identitas dengan priority: verval -> peserta -> potensi"""
    for src in (verval, peserta, potensi):
        if not src:
            continue
        if isinstance(src, dict) and 'ptk' in src:
            ptk = src.get('ptk', {}) or {}
        elif isinstance(src, dict):
            ptk = src.get('ptk', {}) or {}
        else:
            ptk = {}
        if not ptk.get('nama'):
            continue

        # Coba extract dari ptk.ptk_sekolah (struktur peserta/potensi)
        ptk_sekolah = ptk.get('ptk_sekolah')
        if ptk_sekolah:
            sekolah = ptk_sekolah.get('sekolah', {}) or {}
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

        # Fallback: extract dari flat fields (struktur verval_profil.json)
        sekolah_nama = src.get('sekolah')
        if sekolah_nama:
            m_jenjang = src.get('m_jenjang', {}) or {}
            m_kota = src.get('m_kota', {}) or {}
            m_propinsi = src.get('m_propinsi', {}) or {}
            return {
                'nama': ptk.get('nama'),
                'nuptk': ptk.get('nuptk'),
                'no_hp': src.get('no_hp'),
                'no_ukg': ptk.get('no_ukg'),
                'is_guru_dapodik': ptk.get('is_guru_dapodik'),
                'sekolah_nama': sekolah_nama,
                'sekolah_npsn': src.get('npsn'),
                'sekolah_jenjang': m_jenjang.get('singkat') if m_jenjang else None,
                'sekolah_kota': m_kota.get('keterangan') if m_kota else None,
                'sekolah_propinsi': m_propinsi.get('keterangan') if m_propinsi else None,
            }

        # ptk ada tapi tanpa data sekolah, lanjut ke source berikutnya
    return {}


def extract_verval_flat(v):
    """Ekstrak kolom penting dari verval ke flat dict."""
    if not v:
        return {}

    # Nested refs
    m_v = v.get('m_verval_ppg', {}) or {}
    m_j = v.get('m_jenjang', {}) or {}
    m_k = v.get('m_kota', {}) or {}
    m_p = v.get('m_propinsi', {}) or {}
    m_q = v.get('m_kualifikasi', {}) or {}
    m_peg = v.get('m_pegawai', {}) or {}
    m_jp = v.get('m_jurusan_ppg', {}) or {}
    m_mp = v.get('m_mapel_ppg', {}) or {}
    kandidat = (v.get('ppgdj_kandidats') or [None])[0]

    return {
        'verval_ppgdj_mhs_id': v.get('ppgdj_mhs_id'),
        'verval_status': m_v.get('keterangan'),
        'verval_wkt_ajuan': v.get('wkt_ajuan'),
        'verval_wkt_verval': v.get('wkt_verval'),
        'verval_nama': v.get('nama'),
        'verval_nik': v.get('nik'),
        'verval_nuptk': v.get('nuptk'),
        'verval_kelamin': v.get('kelamin'),
        'verval_tmp_lahir': v.get('tmp_lahir'),
        'verval_tgl_lahir': v.get('tgl_lahir'),
        'verval_no_hp': v.get('no_hp'),
        'verval_email_belajar': v.get('email_belajar'),
        'verval_status_kepegawaian': m_peg.get('keterangan'),
        'verval_nip': v.get('nip'),
        'verval_jabatan': v.get('jabatan'),
        'verval_tmt_guru': v.get('tmt_guru'),
        'verval_kualifikasi': m_q.get('singkat'),
        'verval_prodi_s1': v.get('data', {}).get('prodi_s1') if v.get('data') else None,
        'verval_sekolah_nama': v.get('sekolah'),
        'verval_sekolah_npsn': v.get('npsn'),
        'verval_kecamatan': v.get('kecamatan'),
        'verval_jenjang': m_j.get('singkat'),
        'verval_kota': m_k.get('keterangan'),
        'verval_propinsi': m_p.get('keterangan'),
        'verval_jurusan_ppg': m_jp.get('keterangan'),
        'verval_perti_ppg': v.get('perti_ppg'),
        'verval_prodi_ppg': v.get('prodi_ppg'),
        'verval_mapel_ppg': m_mp.get('keterangan'),
        'verval_is_lapor': v.get('is_lapor'),
        'verval_is_undur': v.get('is_undur'),
        'verval_is_peserta': v.get('is_peserta'),
        'verval_is_cadangan': v.get('is_cadangan'),
        'verval_is_plpg': v.get('is_plpg'),
        'verval_is_kasek': v.get('is_kasek'),
        'verval_is_lengkap_pks': v.get('is_lengkap_pks'),
        'verval_is_lengkap_laporan': v.get('is_lengkap_laporan'),
        'verval_is_epks': v.get('is_epks'),
        'verval_kandidat_skor_total_final': kandidat.get('skor_total_final') if kandidat else None,
        'verval_kandidat_is_lulus': kandidat.get('is_lulus') if kandidat else None,
        'verval_kandidat_status_seleksi': kandidat.get('status_seleksi_akademik') if kandidat else None,
        'verval_rekening_nama': v.get('rekening_nama'),
        'verval_rekening_nomor': v.get('rekening_nomor'),
        'verval_rekening_cabang': v.get('rekening_cabang'),
    }


def extract_peserta_flat(p):
    if not p:
        return {}
    return {
        'peserta_id': p.get('ppgdj_kandidat_status_id'),
        'peserta_ppgdj_mhs_id': p.get('ppgdj_mhs_id'),
        'peserta_nik': p.get('nik'),
        'peserta_nik_status': p.get('nik_status'),
        'peserta_pusdatin_status': p.get('pusdatin_status'),
        'peserta_bagren_status': p.get('bagren_status'),
        'peserta_bagren_error': p.get('bagren_error'),
        'peserta_layak_daftar': p.get('layak_daftar'),
        'peserta_keberminatan_status': p.get('keberminatan_status'),
        'peserta_keberminatan_waktu': p.get('keberminatan_waktu'),
        'peserta_keberminatan_alasan': p.get('keberminatan_alasan'),
        'peserta_created_at': p.get('created_at'),
        'peserta_updated_at': p.get('updated_at'),
    }


def extract_potensi_flat(p):
    if not p:
        return {}
    return {
        'potensi_ppgdj_keberminatan_id': p.get('ppgdj_keberminatan_id'),
        'potensi_status': p.get('status'),
        'potensi_alasan': p.get('alasan'),
        'potensi_waktu': p.get('waktu'),
        'potensi_instansi_id': p.get('instansi_id'),
        'potensi_akun_id': p.get('akun_id'),
        'potensi_created_at': p.get('created_at'),
        'potensi_updated_at': p.get('updated_at'),
    }


# ------------------------------------------------------------------
# 1. Load
potensi_list = json.load(open(os.path.join(BASE_DIR, 'potensi_keberminatan.json'), 'r', encoding='utf-8'))
peserta_list = json.load(open(os.path.join(BASE_DIR, 'peserta_berminat.json'), 'r', encoding='utf-8'))
verval_list = json.load(open(os.path.join(BASE_DIR, 'verval_profil.json'), 'r', encoding='utf-8'))

potensi_map = {p['ptk_id']: p for p in potensi_list if p.get('ptk_id')}
peserta_map = {p['ptk_id']: p for p in peserta_list if p.get('ptk_id')}
verval_map = {v['ptk_id']: v for v in verval_list if v.get('ptk_id')}

all_ids = sorted(set(potensi_map) | set(peserta_map) | set(verval_map))

# ------------------------------------------------------------------
# 2. Merge
rows = []
for pid in all_ids:
    pot = potensi_map.get(pid)
    pes = peserta_map.get(pid)
    ver = verval_map.get(pid)

    # Source flags
    row = {
        'ptk_id': pid,
        'has_potensi': 1 if pot else 0,
        'has_peserta': 1 if pes else 0,
        'has_verval': 1 if ver else 0,
    }

    # Deduplicated identity / school (fallback verval -> peserta -> potensi)
    identity = extract_ptk_fallback(pes, pot, ver)
    row.update(identity)

    # Dataset-specific fields
    row.update(extract_potensi_flat(pot))
    row.update(extract_peserta_flat(pes))
    row.update(extract_verval_flat(ver))

    rows.append(row)

# ------------------------------------------------------------------
# 3. Field order
fieldnames = [
    'ptk_id', 'has_potensi', 'has_peserta', 'has_verval',
    # Identity (deduplicated)
    'nama', 'nuptk', 'no_ukg', 'no_hp', 'is_guru_dapodik',
    'sekolah_nama', 'sekolah_npsn', 'sekolah_jenjang',
    'sekolah_kota', 'sekolah_propinsi',
    # Potensi
    'potensi_ppgdj_keberminatan_id', 'potensi_status', 'potensi_alasan',
    'potensi_waktu', 'potensi_instansi_id', 'potensi_akun_id',
    'potensi_created_at', 'potensi_updated_at',
    # Peserta
    'peserta_id', 'peserta_ppgdj_mhs_id', 'peserta_nik', 'peserta_nik_status',
    'peserta_pusdatin_status', 'peserta_bagren_status', 'peserta_bagren_error',
    'peserta_layak_daftar', 'peserta_keberminatan_status',
    'peserta_keberminatan_waktu', 'peserta_keberminatan_alasan',
    'peserta_created_at', 'peserta_updated_at',
    # Verval
    'verval_ppgdj_mhs_id', 'verval_status', 'verval_wkt_ajuan', 'verval_wkt_verval',
    'verval_nama', 'verval_nik', 'verval_nuptk', 'verval_kelamin',
    'verval_tmp_lahir', 'verval_tgl_lahir', 'verval_no_hp', 'verval_email_belajar',
    'verval_status_kepegawaian', 'verval_nip', 'verval_jabatan', 'verval_tmt_guru',
    'verval_kualifikasi', 'verval_prodi_s1',
    'verval_sekolah_nama', 'verval_sekolah_npsn', 'verval_kecamatan',
    'verval_jenjang', 'verval_kota', 'verval_propinsi',
    'verval_jurusan_ppg', 'verval_perti_ppg', 'verval_prodi_ppg', 'verval_mapel_ppg',
    'verval_is_lapor', 'verval_is_undur', 'verval_is_peserta',
    'verval_is_cadangan', 'verval_is_plpg', 'verval_is_kasek',
    'verval_is_lengkap_pks', 'verval_is_lengkap_laporan', 'verval_is_epks',
    'verval_kandidat_skor_total_final', 'verval_kandidat_is_lulus', 'verval_kandidat_status_seleksi',
    'verval_rekening_nama', 'verval_rekening_nomor', 'verval_rekening_cabang',
]

# ------------------------------------------------------------------
# 4. Write CSV
output_path = os.path.join(BASE_DIR, 'merged_3_dataset.csv')
with open(output_path, 'w', newline='', encoding='utf-8-sig') as f:
    writer = csv.DictWriter(f, fieldnames=fieldnames)
    writer.writeheader()
    writer.writerows(rows)

# ------------------------------------------------------------------
# 5. Summary
sources = Counter((r['has_potensi'], r['has_peserta'], r['has_verval']) for r in rows)
print('=' * 60)
print('MERGE 3 DATASET SUMMARY')
print('=' * 60)
print(f'Total rows : {len(rows)}')
print()
print('Kombinasi source (potensi | peserta | verval):')
for (hp, hpe, hv), c in sorted(sources.items()):
    lbl = f'  potensi={hp} | peserta={hpe} | verval={hv}'
    print(f'{lbl}: {c}')
print()
print('Detail kategori:')
print(f'  Hanya Potensi                : {sources.get((1,0,0), 0)}')
print(f'  Potensi + Peserta (no verval): {sources.get((1,1,0), 0)}')
print(f'  Potensi + Peserta + Verval   : {sources.get((1,1,1), 0)}')
print(f'  Hanya Peserta (anomali)      : {sources.get((0,1,0), 0)}')
print(f'  Peserta + Verval (no potensi): {sources.get((0,1,1), 0)}')
print(f'  Hanya Verval                 : {sources.get((0,0,1), 0)}')
print(f'  Potensi + Verval (no peserta): {sources.get((1,0,1), 0)}')
print()
print(f'Output: {output_path}')
print('=' * 60)
print('Done.')
