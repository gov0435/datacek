#!/usr/bin/env python3
"""Gabung potensi_keberminatan.json + peserta_berminat.json via ptk_id."""

from __future__ import annotations

import json
from pathlib import Path


SCRIPT_DIR = Path(__file__).resolve().parent


POTENSI_PATH = SCRIPT_DIR / "potensi_keberminatan.json"
PESERTA_PATH = SCRIPT_DIR / "peserta_berminat.json"
OUTPUT_PATH = SCRIPT_DIR / "gabung_data.json"

MERGE_KEY = "ptk_id"


def _load(path: Path) -> list[dict]:
    return json.loads(path.read_text(encoding="utf-8"))


POTENSI_FROM_PESERTA = (
    "layak_daftar",
    "nik",
    "nik_status",
    "pusdatin_status",
    "pusdatin_antri",
    "pusdatin_waktu",
    "pusdatin_error",
    "bagren_antri",
    "bagren_waktu",
    "bagren_status",
    "bagren_error",
    "push_waktu",
    "push_response",
    "pusdatin_wait",
    "bagren_wait",
    "keberminatan_status",
    "keberminatan_alasan",
    "keberminatan_waktu",
    "keberminatan_tahun_ppg",
    "keberminatan_no_serdik",
    "keberminatan_nim",
    "keberminatan_response",
)


POTENSI_KEYS = (
    "ppgdj_keberminatan_id",
    "tahun",
    "gelombang",
    "ptk_id",
    "status",
    "alasan",
    "waktu",
    "tahun_ppg",
    "no_serdik",
    "nim",
    "response",
    "instansi_id",
    "ppgdj_mhs_id",
    "akun_id",
    "created_at",
    "updated_at",
)


def main() -> int:
    potensi = _load(POTENSI_PATH)
    peserta = _load(PESERTA_PATH)

    peserta_index: dict[str, dict] = {}
    for r in peserta:
        key = r.get(MERGE_KEY)
        if key:
            peserta_index[key] = r

    merged: list[dict] = []
    stats = {
        "total_potensi": len(potensi),
        "total_peserta": len(peserta),
        "matched": 0,
        "potensi_only": 0,
        "peserta_only": 0,
    }

    for r in potensi:
        key = r.get(MERGE_KEY)
        p = peserta_index.pop(key, None) if key else None

        record = {}

        for field in POTENSI_KEYS:
            record[field] = r.get(field)

        record["ptk"] = r.get("ptk")

        if p:
            record["_source"] = "potensi+peserta"
            for field in POTENSI_FROM_PESERTA:
                record[field] = p.get(field)
            record["verifikasi_ptk"] = p.get("ptk")
            record["ppgdj_kandidat_status_id"] = p.get("ppgdj_kandidat_status_id")
            stats["matched"] += 1
        else:
            record["_source"] = "potensi_only"
            for field in POTENSI_FROM_PESERTA:
                record[field] = None
            record["verifikasi_ptk"] = None
            record["ppgdj_kandidat_status_id"] = None
            stats["potensi_only"] += 1

        merged.append(record)

    for key, p in peserta_index.items():
        record = {}

        for field in POTENSI_KEYS:
            record[field] = None
        record["ptk_id"] = key
        record["ptk"] = None

        record["_source"] = "peserta_only"
        for field in POTENSI_FROM_PESERTA:
            record[field] = p.get(field)
        record["verifikasi_ptk"] = p.get("ptk")
        record["ppgdj_kandidat_status_id"] = p.get("ppgdj_kandidat_status_id")

        merged.append(record)
        stats["peserta_only"] += 1

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_PATH.write_text(
        json.dumps(merged, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    print(f"=== Statistik ===")
    for k, v in stats.items():
        print(f"  {k}: {v}")
    print(f"  output: {OUTPUT_PATH}")
    print(f"  merged_records: {len(merged)}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
