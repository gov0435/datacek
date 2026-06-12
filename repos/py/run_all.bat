@echo off
setlocal enabledelayedexpansion

set SCRIPTS_DIR=%~dp0

echo ========================================
echo Mulai eksekusi pipeline 01-04
echo ========================================
echo.

for %%s in (
    "01-fetch_potensi_keberminatan.py"
    "02-fetch_peserta_berminat.py"
    "03-fetch_verval_profil.py"
    "04-merge_3_dataset.py"
) do (
    echo [RUN] %%s
    echo ----------------------------------------
    python "%SCRIPTS_DIR%%%~s"
    if !errorlevel! neq 0 (
        echo ========================================
        echo [FAIL] %%s gagal dengan kode error !errorlevel!
        echo Proses dihentikan.
        echo ========================================
        pause
        exit /b !errorlevel!
    )
    echo [OK] %%s selesai
    echo.
)

echo ========================================
echo Selesai: Semua script berhasil dijalankan
echo ========================================
