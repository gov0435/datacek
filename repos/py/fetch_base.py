#!/usr/bin/env python3
"""Shared utilities for PPG fetch scripts."""

from __future__ import annotations

import json
import os
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any


SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = SCRIPT_DIR.parents[1]
SUPPORTS_COLOR = sys.stdout.isatty() and os.environ.get("NO_COLOR") is None


def color(text: str, code: str) -> str:
    if not SUPPORTS_COLOR:
        return text

    return f"\033[{code}m{text}\033[0m"


def load_dotenv(path: Path) -> None:
    if not path.exists():
        return

    for raw_line in path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()

        if not line or line.startswith("#") or "=" not in line:
            continue

        key, value = line.split("=", 1)
        key = key.strip()
        value = value.strip().strip('"').strip("'")

        if key and key not in os.environ:
            os.environ[key] = value


def env(name: str, default: str | None = None, *, required: bool = False) -> str:
    value = os.environ.get(name, default)

    if required and not value:
        raise RuntimeError(f"Environment variable {name} wajib diisi.")

    return value or ""


def build_cookie_header() -> str:
    full_cookie = env("PPG_COOKIE")

    if full_cookie:
        return full_cookie

    cookie_parts = {
        "CASAuth": env("PPG_CAS_AUTH"),
        "XSRF-TOKEN": env("PPG_XSRF_TOKEN"),
        "s_i_m_p_k_b_p_p_g_session": env("PPG_SESSION"),
    }
    cookie = "; ".join(f"{key}={value}" for key, value in cookie_parts.items() if value)

    if not cookie:
        raise RuntimeError(
            "Isi PPG_COOKIE, atau isi PPG_CAS_AUTH, PPG_XSRF_TOKEN, dan PPG_SESSION."
        )

    return cookie


def extract_records(payload: Any) -> list[Any]:
    if isinstance(payload, list):
        return payload

    if not isinstance(payload, dict):
        return []

    for key in ("data", "items", "results"):
        value = payload.get(key)

        if isinstance(value, list):
            return value

        if isinstance(value, dict):
            nested = extract_records(value)

            if nested:
                return nested

    return []


def extract_pagination(payload: Any) -> dict[str, Any]:
    if not isinstance(payload, dict):
        return {}

    pagination: dict[str, Any] = {
        key: payload.get(key)
        for key in ("current_page", "last_page", "per_page", "total", "from", "to", "has_more_pages")
        if key in payload
    }

    has_more = pagination.get("has_more_pages")

    if has_more is None:
        has_more = pagination.get("has_more")

    if has_more is not None:
        pagination["has_more_pages"] = has_more

    return pagination


def fetch_page(url: str, headers: dict[str, str], params: dict[str, str | int]) -> Any:
    query = urllib.parse.urlencode(params)
    request = urllib.request.Request(f"{url}?{query}", headers=headers, method="GET")

    try:
        with urllib.request.urlopen(request, timeout=60) as response:
            body = response.read().decode("utf-8")
    except urllib.error.HTTPError as error:
        detail = error.read().decode("utf-8", errors="replace")
        raise RuntimeError(f"HTTP {error.code}: {detail[:500]}") from error

    try:
        return json.loads(body)
    except json.JSONDecodeError as error:
        preview = body[:200] if body else "(empty)"
        raise RuntimeError(
            f"JSON decode error: {error}. "
            f"HTTP 200, body={preview!r}"
        ) from error


def print_progress(
    page: int,
    last_page: int | None,
    fetched: int,
    total_fetched: int,
    api_total: int | None,
) -> None:
    if last_page:
        percent = min((page / last_page) * 100, 100)
        width = 30
        filled = int(width * percent / 100)
        bar = color("#" * filled, "32") + color("-" * (width - filled), "90")
        total_label = str(api_total) if api_total is not None else "?"
        message = (
            f"\r{color('[', '90')}{bar}{color(']', '90')} "
            f"{color(f'{percent:6.2f}%', '36')} "
            f"{color('page', '90')} {color(str(page), '33')}/{last_page} "
            f"{color('fetched=', '90')}{fetched} "
            f"{color('total=', '90')}{color(str(total_fetched), '32')}/{total_label}"
        )
    else:
        message = f"\rpage {page} fetched={fetched} total={total_fetched}"

    print(message, end="", flush=True)


def fetch_all(
    *,
    endpoint_env: str = "PPG_ENDPOINT_PATH",
    output_env: str = "PPG_OUTPUT_PATH",
    default_endpoint_path: str = "ppgdj-mahasiswa/keberminatan-verifikasi",
    default_output_path: str | None = None,
    params_overrides: dict[str, str | None] | None = None,
    stats_path: str | None = None,
) -> int:
    load_dotenv(PROJECT_ROOT / ".env")

    backend_base_url = env("PPG_BACKEND_BASE_URL", "https://ppg-backend.simpkb.id")
    instansi_id = env("PPG_INSTANSI_ID", required=True)
    endpoint_path = env(endpoint_env, default_endpoint_path)
    output_path = Path(
        env(output_env, default_output_path or str(SCRIPT_DIR / "potensi_keberminatan.json"))
    )

    if not output_path.is_absolute():
        output_path = PROJECT_ROOT / output_path

    limit = int(env("PPG_LIMIT", "10"))
    delay_seconds = float(env("PPG_DELAY_SECONDS", "3"))
    start_page = int(env("PPG_START_PAGE", "1"))
    max_pages = int(env("PPG_MAX_PAGES", "0"))

    url = f"{backend_base_url.rstrip('/')}/i/{instansi_id}/{endpoint_path.strip('/')}"
    headers = {
        "Accept": "application/json, text/plain, */*",
        "Accept-Language": env("PPG_ACCEPT_LANGUAGE", "en-US,en;q=0.9"),
        "Cookie": build_cookie_header(),
        "Origin": env("PPG_ORIGIN", "https://ppg.simpkb.id"),
        "Referer": env("PPG_REFERER", "https://ppg.simpkb.id/"),
        "User-Agent": env(
            "PPG_USER_AGENT",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36",
        ),
    }
    base_params: dict[str, str | int] = {
        "keyword": env("PPG_KEYWORD", ""),
        "gelombang": env("PPG_GELOMBANG", "2"),
        "tahun": env("PPG_TAHUN", "2026"),
        "limit": limit,
    }

    if params_overrides:
        for key, value in params_overrides.items():
            if value is None:
                base_params.pop(key, None)
            else:
                base_params[key] = value

    known_total: int | None = None

    if stats_path:
        stats_url = f"{backend_base_url.rstrip('/')}/i/{instansi_id}/{stats_path.strip('/')}"
        try:
            stats_payload = fetch_page(stats_url, headers, base_params)
            if isinstance(stats_payload, dict):
                known_total = stats_payload.get("total")
                if not isinstance(known_total, int):
                    known_total = None
        except Exception as exc:
            print(f"warning: gagal fetch stats {exc}")

    records: list[Any] = []
    page = start_page

    while True:
        if max_pages and page >= start_page + max_pages:
            print(f"stop=max_pages max_pages={max_pages}")
            break

        payload = fetch_page(url, headers, {**base_params, "page": page})
        page_records = extract_records(payload)
        pagination = extract_pagination(payload)
        records.extend(page_records)

        current_page = pagination.get("current_page", page)
        last_page = pagination.get("last_page")
        per_page = pagination.get("per_page")
        api_total = pagination.get("total")
        has_more_pages = pagination.get("has_more_pages")

        if not isinstance(api_total, int) and isinstance(known_total, int):
            api_total = known_total

        if not isinstance(last_page, int) and isinstance(api_total, int) and isinstance(per_page, int) and per_page > 0:
            last_page = (api_total + per_page - 1) // per_page

        print_progress(
            page=current_page if isinstance(current_page, int) else page,
            last_page=last_page if isinstance(last_page, int) else None,
            fetched=len(page_records),
            total_fetched=len(records),
            api_total=api_total if isinstance(api_total, int) else None,
        )

        if isinstance(last_page, int) and page >= last_page:
            print(f"\nstop=last_page page={page} last_page={last_page}")
            break

        if isinstance(has_more_pages, bool) and not has_more_pages:
            print(f"\nstop=has_more_pages=False page={page}")
            break

        if not page_records:
            print(f"\nstop=empty_page page={page}")
            break

        page += 1
        time.sleep(delay_seconds)

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(
        json.dumps(records, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    print(f"saved={output_path} records={len(records)}")

    return 0
