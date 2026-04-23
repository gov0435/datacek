<?php

namespace App\Filament\App\Widgets;

use App\Enums\StatusDaftar;
use App\Models\PotensiPpg;
use App\Models\User;
use App\Models\Whitelist;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RegistrationStatsWidget extends BaseWidget
{
    private const JENJANG_KAB_KOTA = ['PAUD', 'SD', 'SMP'];

    private const JENJANG_PROVINSI = ['SLB', 'SMA', 'SMK'];

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        $kabKota = $this->getAuthenticatedWhitelistKabKota();

        // Base query with scope filter
        $query = PotensiPpg::query();

        // Check if user is provinsi scope
        if ($this->isProvinsiScope()) {
            // Filter by jenjang provinsi
            $query->whereIn('jenjang', self::JENJANG_PROVINSI);
        } else {
            // Filter by jenjang kab/kota
            $query->whereIn('jenjang', self::JENJANG_KAB_KOTA);

            // Filter by kabkota if exists
            if ($kabKota !== null) {
                $query->where('kota', $kabKota);
            }
        }

        // Get counts
        $totalCount = $query->count();

        $layakCount = (clone $query)
            ->where('layak_daftar', 'Layak Daftar')
            ->count();

        $sudahDaftarCount = (clone $query)
            ->where('status_daftar', StatusDaftar::SudahDaftar->value)
            ->count();

        return [
            Stat::make('Total Potensi PPG', $totalCount)
                ->description('Guru mendapat notif berminat PPG - '.$kabKota)
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),

            Stat::make('Layak Daftar', $layakCount)
                ->description('Calon peserta yang layak mendaftar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Sudah Daftar', $sudahDaftarCount)
                ->description('Calon peserta yang sudah mendaftar')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),
        ];
    }

    private function isProvinsiScope(): bool
    {
        $kabKota = $this->getAuthenticatedWhitelistKabKota();

        if (is_string($kabKota) && str_contains(strtolower($kabKota), 'provinsi')) {
            return true;
        }

        return false;
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
