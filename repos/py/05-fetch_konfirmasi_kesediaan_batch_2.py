#!/usr/bin/env python3
"""Fetch data konfirmasi kesediaan PPG batch 2 and save it as JSON."""

from __future__ import annotations

import sys

from fetch_base import fetch_all


if __name__ == "__main__":
    try:
        raise SystemExit(
            fetch_all(
                endpoint_env="PPG_KONFIRMASI_KESEDIAAN_ENDPOINT_PATH",
                output_env="PPG_KONFIRMASI_KESEDIAAN_OUTPUT_PATH",
                default_endpoint_path="ppgdj-mahasiswa/konfirmasi-kesediaan",
                default_output_path="repos/py/konfirmasi_kesediaan_batch_2.json",
                params_overrides={"gelombang": "3"},
            )
        )
    except Exception as error:
        print(f"error: {error}", file=sys.stderr)
        raise SystemExit(1) from error
