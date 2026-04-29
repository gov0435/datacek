<?php

namespace App\Filament\Widgets;

use App\Enums\KabKota;
use App\Enums\StatusPPG;
use App\Models\PotensiPpg;
use Filament\Widgets\ChartWidget;

class StatusPPGByRegionChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Status PPG Berdasarkan Wilayah';

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

        $statusCases = StatusPPG::cases();
        $kabKotaList = array_slice($regions, 1); // exclude Provinsi

        $datasets = [];

        foreach ($statusCases as $status) {
            $data = [];
            foreach ($regions as $region) {
                $query = PotensiPpg::query()->where('statusppg', $status->value);

                if ($region === 'Provinsi') {
                    // Provinsi includes SLB, SMA, SMK from all regions
                    $query->whereIn('jenjang', ['SLB', 'SMA', 'SMK']);
                } else {
                    // Kab/Kota includes PAUD, SD, SMP from that region
                    $query->where('kota', $region)->whereIn('jenjang', ['PAUD', 'SD', 'SMP']);
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

        // Add null status count
        $nullData = [];
        foreach ($regions as $region) {
            $query = PotensiPpg::query()->whereNull('statusppg');

            if ($region === 'Provinsi') {
                $query->whereIn('jenjang', ['SLB', 'SMA', 'SMK']);
            } else {
                $query->where('kota', $region)->whereIn('jenjang', ['PAUD', 'SD', 'SMP']);
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

        return [
            'labels' => $regions,
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

    private function getColorForStatus(StatusPPG $status): string
    {
        return match ($status) {
            StatusPPG::SudahSerdik => '#10B981',       // green (success)
            StatusPPG::SementaraSerdik => '#3B82F6',   // blue (info)
            StatusPPG::BelumSerdik => '#F59E0B',       // amber (warning)
            StatusPPG::BelumS1 => '#8e7cc3',           // purple
            StatusPPG::BukanGuru => '#EF4444',         // red (danger)
            StatusPPG::Meninggal => '#DC2626',         // dark red (danger)
            StatusPPG::TidakAktif => '#991B1B',        // darker red (danger)
            StatusPPG::Pensiun => '#7C2D12',           // dark orange (danger)
        };
    }
}
