-- Neon PostgreSQL: tabel survey_ppg untuk import repos/py/merged_3_dataset.csv
-- Jalankan di Neon SQL Editor / psql.
-- 78 kolom dari CSV + 1 kolom manual `keterangan` (tidak pernah ditimpa import).

DROP TABLE IF EXISTS survey_ppg;

CREATE TABLE survey_ppg (
    -- Kunci & flag sumber
    ptk_id                            BIGINT PRIMARY KEY,
    has_potensi                       BOOLEAN,
    has_peserta                       BOOLEAN,
    has_verval                        BOOLEAN,

    -- Identitas (deduplicated: verval -> peserta -> potensi)
    nama                              TEXT,
    nuptk                             TEXT,
    no_ukg                            TEXT,
    no_hp                             TEXT,
    is_guru_dapodik                   BOOLEAN,
    sekolah_nama                      TEXT,
    sekolah_npsn                      TEXT,
    sekolah_jenjang                   TEXT,
    sekolah_kota                      TEXT,
    sekolah_propinsi                  TEXT,

    -- Potensi keberminatan
    potensi_ppgdj_keberminatan_id     BIGINT,
    potensi_status                    TEXT,
    potensi_alasan                    TEXT,
    potensi_waktu                     TIMESTAMPTZ,
    potensi_instansi_id               BIGINT,
    potensi_akun_id                   BIGINT,
    potensi_created_at                TIMESTAMPTZ,
    potensi_updated_at                TIMESTAMPTZ,

    -- Peserta berminat
    peserta_id                        BIGINT,
    peserta_ppgdj_mhs_id              BIGINT,
    peserta_nik                       TEXT,
    peserta_nik_status                TEXT,
    peserta_pusdatin_status           TEXT,
    peserta_bagren_status             TEXT,
    peserta_bagren_error              TEXT,
    peserta_layak_daftar              TEXT,
    peserta_keberminatan_status       TEXT,
    peserta_keberminatan_waktu        TIMESTAMPTZ,
    peserta_keberminatan_alasan       TEXT,
    peserta_created_at                TIMESTAMPTZ,
    peserta_updated_at                TIMESTAMPTZ,

    -- Verval profil
    verval_ppgdj_mhs_id               BIGINT,
    verval_status                     TEXT,
    verval_wkt_ajuan                  TIMESTAMPTZ,
    verval_wkt_verval                 TIMESTAMPTZ,
    verval_nama                       TEXT,
    verval_nik                        TEXT,
    verval_nuptk                      TEXT,
    verval_kelamin                    TEXT,
    verval_tmp_lahir                  TEXT,
    verval_tgl_lahir                  DATE,
    verval_no_hp                      TEXT,
    verval_email_belajar              TEXT,
    verval_status_kepegawaian         TEXT,
    verval_nip                        TEXT,
    verval_jabatan                    TEXT,
    verval_tmt_guru                   DATE,
    verval_kualifikasi                TEXT,
    verval_prodi_s1                   TEXT,
    verval_sekolah_nama               TEXT,
    verval_sekolah_npsn               TEXT,
    verval_kecamatan                  TEXT,
    verval_jenjang                    TEXT,
    verval_kota                       TEXT,
    verval_propinsi                   TEXT,
    verval_jurusan_ppg                TEXT,
    verval_perti_ppg                  TEXT,
    verval_prodi_ppg                  TEXT,
    verval_mapel_ppg                  TEXT,
    verval_is_lapor                   BOOLEAN,
    verval_is_undur                   BOOLEAN,
    verval_is_peserta                 BOOLEAN,
    verval_is_cadangan                BOOLEAN,
    verval_is_plpg                    BOOLEAN,
    verval_is_kasek                   BOOLEAN,
    verval_is_lengkap_pks             BOOLEAN,
    verval_is_lengkap_laporan         BOOLEAN,
    verval_is_epks                    BOOLEAN,
    verval_kandidat_skor_total_final  NUMERIC,
    verval_kandidat_is_lulus          BOOLEAN,
    verval_kandidat_status_seleksi    TEXT,
    verval_rekening_nama              TEXT,
    verval_rekening_nomor             TEXT,
    verval_rekening_cabang            TEXT,

    -- Kolom manual (di luar 78 kolom CSV) — diisi user, TIDAK pernah ditimpa import
    keterangan                        TEXT
);

-- Index bantu untuk query umum
CREATE INDEX idx_survey_ppg_has_flags    ON survey_ppg (has_potensi, has_peserta, has_verval);
CREATE INDEX idx_survey_ppg_nik          ON survey_ppg (peserta_nik);
CREATE INDEX idx_survey_ppg_layak_daftar ON survey_ppg (peserta_layak_daftar);
