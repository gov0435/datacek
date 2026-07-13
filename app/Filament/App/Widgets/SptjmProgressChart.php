<?php

namespace App\Filament\App\Widgets;

use App\Models\SptjmSekolah;
use App\Models\User;
use App\Models\Whitelist;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SptjmProgressChart extends ChartWidget
{
    protected ?string $heading = 'Progress Dokumen SPTJM';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $kabKota = $this->getAuthenticatedWhitelistKabKota();

        // Base query with scope filter
        $query = SptjmSekolah::query();

        if ($kabKota === null) {
            $query->whereRaw('1 = 0');
            $jenjangs = [];
        } elseif (str_contains(strtolower($kabKota), 'provinsi')) {
            $jenjangs = ['SLB', 'SMA', 'SMK'];
            $query->where('scope', 'provinsi');
        } else {
            $jenjangs = ['PAUD', 'SD', 'SMP', 'Lainnya'];
            $query->where('scope', 'kabkota')
                ->where('sekolah_kota', $kabKota);
        }

        $valid = [];
        $pending = [];
        $belumUpload = [];
        $hardcopyDiterima = [];
        $hardcopyBelum = [];

        foreach ($jenjangs as $jenjang) {
            $subQuery = (clone $query)->where('sekolah_jenjang', $jenjang);

            $total = (clone $subQuery)->count();

            $validCount = (clone $subQuery)->where('is_valid', true)->count();
            $pendingCount = (clone $subQuery)->where('is_valid', false)->whereHas('unggahan')->count();
            $belumCount = (clone $subQuery)->where('is_valid', false)->whereDoesntHave('unggahan')->count();

            $hcDiterimaCount = (clone $subQuery)->where('has_hardcopy', true)->count();
            $hcBelumCount = $total - $hcDiterimaCount;

            $valid[] = $validCount;
            $pending[] = $pendingCount;
            $belumUpload[] = $belumCount;
            $hardcopyDiterima[] = $hcDiterimaCount;
            $hardcopyBelum[] = $hcBelumCount;
        }

        return [
            'labels' => $jenjangs,
            'datasets' => [
                [
                    'label' => 'Valid',
                    'data' => $valid,
                    'stack' => 'dokumen',
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#10B981',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Proses Validasi',
                    'data' => $pending,
                    'stack' => 'dokumen',
                    'backgroundColor' => '#F59E0B',
                    'borderColor' => '#F59E0B',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Belum Diupload',
                    'data' => $belumUpload,
                    'stack' => 'dokumen',
                    'backgroundColor' => '#9CA3AF',
                    'borderColor' => '#9CA3AF',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Hardcopy Diterima',
                    'data' => $hardcopyDiterima,
                    'stack' => 'hardcopy',
                    'backgroundColor' => '#3B82F6',
                    'borderColor' => '#3B82F6',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Hardcopy Belum Diterima',
                    'data' => $hardcopyBelum,
                    'stack' => 'hardcopy',
                    'backgroundColor' => '#EF4444',
                    'borderColor' => '#EF4444',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function getAuthenticatedWhitelistKabKota(): ?string
    {
        $whitelist = $this->getAuthenticatedWhitelist();

        if ($whitelist === null) {
            return null;
        }

        return $this->extractKabKotaValue($whitelist);
    }

    private function extractKabKotaValue(Whitelist $model): ?string
    {
        $enumValue = $model->kabkota?->value;

        if (is_string($enumValue) && $enumValue !== '') {
            return $enumValue;
        }

        $rawValue = $model->getRawOriginal('kabkota');

        if (is_string($rawValue) && $rawValue !== '') {
            return $rawValue;
        }

        return null;
    }

    private function getAuthenticatedWhitelist(): ?Whitelist
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return Whitelist::query()
            ->whereRaw('LOWER(email) = ?', [strtolower((string) $user->email)])
            ->first();
    }
}
