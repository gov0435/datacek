#!/usr/bin/env python3
"""Fetch data peserta berminat PPG and save it as JSON."""

from __future__ import annotations

import sys

from fetch_base import fetch_all


if __name__ == "__main__":
    try:
        raise SystemExit(
            fetch_all(
                endpoint_env="PPG_PESERTA_BERMINAT_ENDPOINT_PATH",
                output_env="PPG_PESERTA_BERMINAT_OUTPUT_PATH",
                default_endpoint_path="ppgdj-mahasiswa/keberminatan",
                default_output_path="repos/py/peserta_berminat.json",
            )
        )
    except Exception as error:
        print(f"error: {error}", file=sys.stderr)
        raise SystemExit(1) from error
