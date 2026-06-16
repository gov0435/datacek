-- Neon PostgreSQL: Tabel untuk SPTJM Sekolah, Berita Acara Dinas & Dokumen Dinas
-- Jalankan di Neon SQL Editor / psql.
-- Includes: sptjm_sekolah, sptjm_unggahan, dokumen_dinas, alter whitelists.role
--
-- Domain:
--   SPTJM (Surat Pernyataan Tanggung Jawab Mutlak) → per sekolah
--   Berita Acara → per dinas/wilayah (menggunakan dokumen_dinas dengan jenis = 'Berita Acara')

-- ============================================================================
-- 1. CREATE sptjm_sekolah (master per sekolah)
-- ============================================================================

-- DROP TABLE IF EXISTS sptjm_unggahan CASCADE;
-- DROP TABLE IF EXISTS sptjm_sekolah CASCADE;

CREATE TABLE sptjm_sekolah (
    id BIGSERIAL PRIMARY KEY,
    sekolah_npsn VARCHAR(255) NOT NULL UNIQUE,
    sekolah_nama VARCHAR(255),
    sekolah_jenjang VARCHAR(255),
    sekolah_kota VARCHAR(255),
    sekolah_propinsi VARCHAR(255),
    scope VARCHAR(50),                          -- 'kabkota' atau 'provinsi'
    jumlah_guru INTEGER DEFAULT 0,             -- snapshot jumlah guru non-Berminat saat generate
    is_valid BOOLEAN DEFAULT FALSE,            -- penanda validasi fisik oleh KGTK
    generated_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sptjm_sekolah_npsn ON sptjm_sekolah(sekolah_npsn);
CREATE INDEX idx_sptjm_sekolah_kota ON sptjm_sekolah(sekolah_kota);
CREATE INDEX idx_sptjm_sekolah_propinsi ON sptjm_sekolah(sekolah_propinsi);

-- ============================================================================
-- 2. CREATE sptjm_unggahan (riwayat file versioned)
-- ============================================================================

CREATE TABLE sptjm_unggahan (
    id BIGSERIAL PRIMARY KEY,
    sptjm_sekolah_id BIGINT NOT NULL REFERENCES sptjm_sekolah(id) ON DELETE CASCADE,
    disk VARCHAR(255) DEFAULT 's3',
    file_path VARCHAR(255) NOT NULL,            -- object key di S3
    file_name VARCHAR(255) NOT NULL,            -- nama asli file
    file_mime VARCHAR(255),
    file_size BIGINT,                           -- bytes
    catatan TEXT,
    uploaded_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sptjm_unggahan_sekolah ON sptjm_unggahan(sptjm_sekolah_id);

-- ============================================================================
-- 3. CREATE dokumen_dinas (Berita Acara & dokumen tingkat dinas lainnya)
--    Berita Acara → disimpan sebagai record dokumen_dinas dengan jenis = 'berita_acara'
--    (BUKAN menggunakan tabel sekolah/SPTJM)
-- ============================================================================

-- DROP TABLE IF EXISTS dokumen_dinas CASCADE;

CREATE TABLE dokumen_dinas (
    id BIGSERIAL PRIMARY KEY,
    kabkota VARCHAR(255) NOT NULL,              -- scope dinas (value KabKota enum, incl. 'Provinsi')
    jenis VARCHAR(255) NOT NULL,                -- jenis dokumen: 'berita_acara', 'dokumen_lain'
    disk VARCHAR(255) DEFAULT 's3',
    file_path VARCHAR(255) NOT NULL,            -- object key di S3
    file_name VARCHAR(255) NOT NULL,            -- nama asli file
    file_mime VARCHAR(255),
    file_size BIGINT,                           -- bytes
    is_valid BOOLEAN DEFAULT TRUE,              -- penanda versi valid terakhir per (kabkota, jenis)
    catatan TEXT,
    uploaded_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_dokumen_dinas_scope_jenis_valid ON dokumen_dinas(kabkota, jenis, is_valid);

-- ============================================================================
-- 4. ALTER whitelists: tambah kolom role
-- ============================================================================

ALTER TABLE whitelists
ADD COLUMN role VARCHAR(255) DEFAULT 'member';

-- ============================================================================
-- 5. ADD INDEX pada survey_ppg untuk optimasi query guru
-- ============================================================================

CREATE INDEX idx_survey_ppg_npsn_status ON survey_ppg(sekolah_npsn, potensi_status);
