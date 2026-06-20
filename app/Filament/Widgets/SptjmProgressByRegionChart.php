<?php

namespace App\Filament\Widgets;

use App\Enums\KabKota;
use App\Models\SptjmSekolah;
use Filament\Widgets\ChartWidget;

class SptjmProgressByRegionChart extends ChartWidget
{
    protected ?string $heading = 'Progress Dokumen SPTJM per Wilayah';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxContentHeight = '400px';

    protected function getData(): array
    {
        $regions = [
            KabKota::KabBoalemo->value,
            KabKota::KabBonebolango->value,
            KabKota::KabGorontalo->value,
            KabKota::KabGorontaloUtara->value,
            KabKota::KabPohuwato->value,
            KabKota::KotaGorontalo->value,
            KabKota::Provinsi->value,
        ];

        $belumUpload = [];
        $pending = [];
        $valid = [];

        foreach ($regions as $region) {
            $query = SptjmSekolah::query();

            if ($region === KabKota::Provinsi->value) {
                $query->where('scope', 'provinsi');
            } else {
                $query->where('scope', 'kabkota')->where('sekolah_kota', $region);
            }

            $total = (clone $query)->count();
            $validCount = (clone $query)->where('is_valid', true)->count();

            $hasUpload = (clone $query)->where('is_valid', false)->whereHas('unggahan')->count();
            $belumCount = $total - $validCount - $hasUpload;

            $belumUpload[] = $belumCount;
            $pending[] = $hasUpload;
            $valid[] = $validCount;
        }

        $labels = array_map(fn (string $region, int $i) => $region.' ('.($belumUpload[$i] + $pending[$i] + $valid[$i]).')', $regions, array_keys($regions));

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Valid',
                    'data' => $valid,
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#10B981',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Pending',
                    'data' => $pending,
                    'backgroundColor' => '#F59E0B',
                    'borderColor' => '#F59E0B',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Belum Diupload',
                    'data' => $belumUpload,
                    'backgroundColor' => '#9CA3AF',
                    'borderColor' => '#9CA3AF',
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
}
