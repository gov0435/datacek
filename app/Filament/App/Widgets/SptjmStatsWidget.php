<?php

namespace App\Filament\App\Widgets;

use App\Models\SptjmSekolah;
use App\Models\User;
use App\Models\Whitelist;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SptjmStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $query = $this->getScopedQuery();

        $total = (clone $query)->count();

        $valid = (clone $query)
            ->where('is_valid', true)
            ->count();

        $belumUpload = (clone $query)
            ->where('is_valid', false)
            ->whereDoesntHave('unggahan')
            ->count();

        return [
            Stat::make('Total SPTJM', $total)
                ->description('Seluruh sekolah')
                ->descriptionIcon('heroicon-m-document')
                ->color('info'),

            Stat::make('Belum Upload', $belumUpload)
                ->description('Belum mengunggah SPTJM')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Valid', $valid)
                ->description('Dokumen SPTJM tervalidasi')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }

    private function getScopedQuery()
    {
        $query = SptjmSekolah::query();
        $kabKota = $this->getAuthenticatedWhitelistKabKota();

        if ($kabKota === null) {
            return $query->whereRaw('1 = 0');
        }

        if (str_contains(strtolower($kabKota), 'provinsi')) {
            return $query->whereIn('sekolah_jenjang', ['SLB', 'SMA', 'SMK']);
        }

        return $query->where('sekolah_kota', $kabKota);
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
