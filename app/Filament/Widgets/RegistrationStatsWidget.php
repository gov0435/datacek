<?php

namespace App\Filament\Widgets;

use App\Enums\PotensiStatus;
use App\Models\SurveyPpg;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RegistrationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPotensi = SurveyPpg::query()->where('has_potensi', true)->count();

        $berminat = SurveyPpg::query()
            ->where('has_potensi', true)
            ->where('potensi_status', PotensiStatus::Berminat->value)
            ->count();

        $tidakBerminat = SurveyPpg::query()
            ->where('has_potensi', true)
            ->where('potensi_status', PotensiStatus::TidakBerminat->value)
            ->count();

        return [
            Stat::make('Total Potensi', $totalPotensi)
                ->description('Total Guru aktif yang belum serdik')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Berminat', $berminat)
                ->description('Guru yang berminat mengikuti PPG')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('success'),

            Stat::make('Tidak Berminat', $tidakBerminat)
                ->description('Guru yang tidak berminat mengikuti PPG')
                ->descriptionIcon('heroicon-m-hand-thumb-down')
                ->color('danger'),
        ];
    }
}
