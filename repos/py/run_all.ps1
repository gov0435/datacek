$ScriptsDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$scripts = @(
    "01-fetch_potensi_keberminatan.py"
    "02-fetch_peserta_berminat.py"
    "03-fetch_verval_profil.py"
    "04-merge_3_dataset.py"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Mulai eksekusi pipeline 01-04" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

foreach ($script in $scripts) {
    Write-Host "[RUN] $script" -ForegroundColor Yellow
    Write-Host "----------------------------------------" -ForegroundColor Gray
    $proc = Start-Process -FilePath "python" -ArgumentList @("$ScriptsDir\$script") -NoNewWindow -Wait -PassThru
    if ($proc.ExitCode -ne 0) {
        Write-Host "========================================" -ForegroundColor Red
        Write-Host "[FAIL] $script gagal dengan kode error $($proc.ExitCode)" -ForegroundColor Red
        Write-Host "Proses dihentikan." -ForegroundColor Red
        Write-Host "========================================" -ForegroundColor Red
        Read-Host "Tekan Enter untuk menutup"
        exit $proc.ExitCode
    }
    Write-Host "[OK] $script selesai" -ForegroundColor Green
    Write-Host ""
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Selesai: Semua script berhasil dijalankan" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Read-Host "Tekan Enter untuk menutup"
