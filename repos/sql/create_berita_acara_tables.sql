-- Neon PostgreSQL: Tabel untuk Berita Acara Sekolah & Dokumen Dinas
-- Jalankan di Neon SQL Editor / psql.
-- Includes: berita_acara_sekolah, berita_acara_unggahan, dokumen_dinas, alter whitelists.role

-- ============================================================================
-- 1. CREATE berita_acara_sekolah (master per sekolah)
-- ============================================================================

DROP TABLE IF EXISTS berita_acara_sekolah CASCADE;

CREATE TABLE berita_acara_sekolah (
    id BIGSERIAL PRIMARY KEY,
    sekolah_npsn VARCHAR(255) NOT NULL UNIQUE,
    sekolah_nama VARCHAR(255),
    sekolah_jenjang VARCHAR(255),
    sekolah_kota VARCHAR(255),
    sekolah_propinsi VARCHAR(255),
    scope VARCHAR(50),                          -- 'kabkota' atau 'provinsi'
    jumlah_guru INTEGER UNSIGNED DEFAULT 0,    -- snapshot jumlah guru non-Berminat saat generate
    generated_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_berita_acara_sekolah_npsn ON berita_acara_sekolah(sekolah_npsn);
CREATE INDEX idx_berita_acara_sekolah_kota ON berita_acara_sekolah(sekolah_kota);
CREATE INDEX idx_berita_acara_sekolah_propinsi ON berita_acara_sekolah(sekolah_propinsi);

-- ============================================================================
-- 2. CREATE berita_acara_unggahan (riwayat file versioned)
-- ============================================================================

DROP TABLE IF EXISTS berita_acara_unggahan CASCADE;

CREATE TABLE berita_acara_unggahan (
    id BIGSERIAL PRIMARY KEY,
    berita_acara_sekolah_id BIGINT NOT NULL REFERENCES berita_acara_sekolah(id) ON DELETE CASCADE,
    disk VARCHAR(255) DEFAULT 's3',
    file_path VARCHAR(255) NOT NULL,            -- object key di S3
    file_name VARCHAR(255) NOT NULL,            -- nama asli file
    file_mime VARCHAR(255),
    file_size BIGINT,                           -- bytes
    is_valid BOOLEAN DEFAULT TRUE,              -- penanda file valid terakhir
    catatan TEXT,
    uploaded_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_berita_acara_unggahan_sekolah_valid ON berita_acara_unggahan(berita_acara_sekolah_id, is_valid);

-- ============================================================================
-- 3. CREATE dokumen_dinas (dokumen tingkat dinas, versioned per jenis)
-- ============================================================================

DROP TABLE IF EXISTS dokumen_dinas CASCADE;

CREATE TABLE dokumen_dinas (
    id BIGSERIAL PRIMARY KEY,
    kabkota VARCHAR(255) NOT NULL,              -- scope dinas (value KabKota enum, incl. 'Provinsi')
    jenis VARCHAR(255) NOT NULL,                -- jenis dokumen: 'BeritaAcara', 'DokumenLain'
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
