<?php

namespace App\Filament\Widgets;

use App\Enums\KabKota;
use App\Enums\PotensiStatus;
use App\Models\SurveyPpg;
use Filament\Widgets\ChartWidget;

class StatusPPGByRegionChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Keberminatan PPG Berdasarkan Wilayah';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxContentHeight = '400px';

    protected function getData(): array
    {
        $regions = [
            'Provinsi',
            KabKota::KotaGorontalo->value,
            KabKota::KabGorontalo->value,
            KabKota::KabBoalemo->value,
            KabKota::KabBonebolango->value,
            KabKota::KabGorontaloUtara->value,
            KabKota::KabPohuwato->value,
        ];

        $statusCases = PotensiStatus::cases();

        $datasets = [];

        foreach ($statusCases as $status) {
            $data = [];
            foreach ($regions as $region) {
                $query = SurveyPpg::query()->where('has_potensi', true)->where('potensi_status', $status->value);

                if ($region === 'Provinsi') {
                    $query->whereIn('sekolah_jenjang', ['SLB', 'SMA', 'SMK']);
                } else {
                    $query->where('sekolah_kota', $region)->whereIn('sekolah_jenjang', ['PAUD', 'SD', 'SMP', 'Lainnya']);
                }

                $data[] = $query->count();
            }

            $datasets[] = [
                'label' => $status->getLabel(),
                'data' => $data,
                'backgroundColor' => $this->getColorForStatus($status),
                'borderColor' => $this->getColorForStatus($status),
                'borderWidth' => 1,
            ];
        }

        $nullData = [];
        foreach ($regions as $region) {
            $query = SurveyPpg::query()->where('has_potensi', true)->whereNull('potensi_status');

            if ($region === 'Provinsi') {
                $query->whereIn('sekolah_jenjang', ['SLB', 'SMA', 'SMK']);
            } else {
                $query->where('sekolah_kota', $region)->whereIn('sekolah_jenjang', ['PAUD', 'SD', 'SMP', 'Lainnya']);
            }

            $nullData[] = $query->count();
        }

        if (array_sum($nullData) > 0) {
            $datasets[] = [
                'label' => 'Tidak Diisi',
                'data' => $nullData,
                'backgroundColor' => '#9CA3AF',
                'borderColor' => '#9CA3AF',
                'borderWidth' => 1,
            ];
        }

        $totals = array_fill(0, count($regions), 0);
        foreach ($datasets as $dataset) {
            foreach ($dataset['data'] as $i => $value) {
                $totals[$i] += $value;
            }
        }

        $labels = array_map(fn (string $region, int $i) => $region.' ('.$totals[$i].')', $regions, array_keys($regions));

        return [
            'labels' => $labels,
            'datasets' => $datasets,
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

    private function getColorForStatus(PotensiStatus $status): string
    {
        return match ($status) {
            PotensiStatus::Berminat => '#10B981',
            PotensiStatus::SedangPPG => '#3B82F6',
            PotensiStatus::SudahBerserdik => '#8B5CF6',
            PotensiStatus::TidakBerminat => '#EF4444',
        };
    }
}
