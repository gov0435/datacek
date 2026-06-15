<?php

namespace App\Filament\App\Widgets;

use App\Enums\PotensiStatus;
use App\Models\SurveyPpg;
use App\Models\User;
use App\Models\Whitelist;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RegistrationStatsWidget extends BaseWidget
{
    private const JENJANG_KAB_KOTA = ['PAUD', 'SD', 'SMP', 'Lainnya'];

    private const JENJANG_PROVINSI = ['SLB', 'SMA', 'SMK'];

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $kabKota = $this->getAuthenticatedWhitelistKabKota();

        // Base query with scope filter
        $query = SurveyPpg::query()->where('has_potensi', true);

        if ($kabKota === null) {
            // Admin tanpa whitelist — lihat semua data tanpa filter
        } elseif ($this->isProvinsiScope()) {
            // Filter by jenjang provinsi
            $query->whereIn('sekolah_jenjang', self::JENJANG_PROVINSI);
        } else {
            // Filter by jenjang kab/kota
            $query->whereIn('sekolah_jenjang', self::JENJANG_KAB_KOTA);

            // Filter by kabkota if exists
            if ($kabKota !== null) {
                $query->where('sekolah_kota', $kabKota);
            }
        }

        // Get counts
        $totalCount = $query->count();

        $belumMengisiCount = (clone $query)
            ->whereNull('potensi_status')
            ->count();

        $berminatCount = (clone $query)
            ->where('potensi_status', PotensiStatus::Berminat->value)
            ->count();

        $sedangPpgCount = (clone $query)
            ->where('potensi_status', PotensiStatus::SedangPPG->value)
            ->count();

        $sudahBerserdikCount = (clone $query)
            ->where('potensi_status', PotensiStatus::SudahBerserdik->value)
            ->count();

        $tidakBerminatCount = (clone $query)
            ->where('potensi_status', PotensiStatus::TidakBerminat->value)
            ->count();

        return [
            Stat::make('Total', $totalCount)
                ->description($kabKota ? 'Guru di '.$kabKota : 'Seluruh Guru')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),

            Stat::make('Belum Mengisi', $belumMengisiCount)
                ->description('Belum mengisi survey')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('gray'),

            Stat::make('Berminat', $berminatCount)
                ->description('Menyatakan berminat PPG')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('success'),

            Stat::make('Sedang PPG', $sedangPpgCount)
                ->description('Sedang dalam proses PPG')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Sudah Berserdik', $sudahBerserdikCount)
                ->description('Sudah memiliki sertifikat')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('gray'),

            Stat::make('Tidak Berminat', $tidakBerminatCount)
                ->description('Tidak berminat mengikuti PPG')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
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
