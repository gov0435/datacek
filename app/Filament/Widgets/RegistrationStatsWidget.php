<?php

namespace App\Filament\Widgets;

use App\Enums\LayakDaftar;
use App\Enums\StatusDaftar;
use App\Models\PotensiPpg;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RegistrationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Count by layak_daftar
        $layakDaftarCounts = PotensiPpg::query()
            ->whereNotNull('layak_daftar')
            ->get()
            ->groupBy('layak_daftar')
            ->map(fn ($group) => $group->count())
            ->all();

        // Count by status_ajuan
        $statusAjuanCounts = PotensiPpg::query()
            ->whereNotNull('status_ajuan')
            ->get()
            ->groupBy('status_ajuan')
            ->map(fn ($group) => $group->count())
            ->all();

        // Count by status_daftar
        $statusDaftarCounts = PotensiPpg::query()
            ->whereNotNull('status_daftar')
            ->get()
            ->groupBy('status_daftar')
            ->map(fn ($group) => $group->count())
            ->all();

        $stats = [];

        // Layak Daftar stats
        $stats[] = Stat::make('Layak Daftar', $layakDaftarCounts[LayakDaftar::Layak->value] ?? 0)
            ->description('Calon peserta yang layak mendaftar')
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success');

        $stats[] = Stat::make('Tidak Layak Daftar', $layakDaftarCounts[LayakDaftar::TidakLayak->value] ?? 0)
            ->description('Calon peserta yang tidak layak mendaftar')
            ->descriptionIcon('heroicon-m-x-circle')
            ->color('danger');

        // Status Ajuan stats
        $stats[] = Stat::make('Sudah Ajuan', $statusAjuanCounts['Pendaftar Sudah Ajuan'] ?? 0)
            ->description('Calon peserta yang sudah melakukan ajuan')
            ->descriptionIcon('heroicon-m-paper-airplane')
            ->color('info');

        $stats[] = Stat::make('Belum Ajuan', $statusAjuanCounts['Pendaftar Belum Ajuan'] ?? 0)
            ->description('Calon peserta yang belum melakukan ajuan')
            ->descriptionIcon('heroicon-m-clock')
            ->color('warning');

        // Status Daftar stats
        foreach (StatusDaftar::cases() as $status) {
            $count = $statusDaftarCounts[$status->value] ?? 0;
            $stats[] = Stat::make("Daftar: {$status->getLabel()}", $count)
                ->color($status->getColor());
        }

        return $stats;
    }
}
