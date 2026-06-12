#!/usr/bin/env python3
"""Fetch data verval profil PPG and save it as JSON."""

from __future__ import annotations

import sys

from fetch_base import fetch_all


if __name__ == "__main__":
    try:
        raise SystemExit(
            fetch_all(
                endpoint_env="PPG_VERVAL_ENDPOINT_PATH",
                output_env="PPG_VERVAL_OUTPUT_PATH",
                default_endpoint_path="ppgdj-mahasiswa/verval",
                default_output_path="repos/py/verval_profil.json",
                params_overrides={"tahun": None},
                stats_path="ppgdj-mahasiswa/verval/statistik",
            )
        )
    except Exception as error:
        print(f"error: {error}", file=sys.stderr)
        raise SystemExit(1) from error
