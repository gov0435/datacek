#!/usr/bin/env python3
"""Fetch data keberminatan PPG and save it as JSON."""

from __future__ import annotations

import sys

from fetch_base import fetch_all


if __name__ == "__main__":
    try:
        raise SystemExit(fetch_all())
    except Exception as error:
        print(f"error: {error}", file=sys.stderr)
        raise SystemExit(1) from error
