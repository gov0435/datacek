<?php

namespace App\Filament\Widgets;

use App\Enums\StatusPPG;
use App\Models\PotensiPpg;
use App\Models\User;
use App\Models\Whitelist;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatusPPGDistribution extends BaseWidget
{
    private const JENJANG_KAB_KOTA = ['PAUD', 'SD', 'SMP'];

    private const JENJANG_PROVINSI = ['SLB', 'SMA', 'SMK'];

    protected function getStats(): array
    {
        $query = PotensiPpg::query();

        // Check if user is provinsi scope
        if ($this->isProvinsiScope()) {
            // Filter by jenjang provinsi
            $query->whereIn('jenjang', self::JENJANG_PROVINSI);
        } else {
            // Filter by jenjang kab/kota
            $query->whereIn('jenjang', self::JENJANG_KAB_KOTA);

            // Filter by kabkota dari whitelist user yang login
            $kabKota = $this->getAuthenticatedWhitelistKabKota();

            if ($kabKota !== null) {
                $query->where('kota', $kabKota);
            }
        }

        // Single query to get all counts
        $counts = $query
            ->get()
            ->groupBy('statusppg')
            ->map(fn ($group) => $group->count())
            ->all();

        $stats = [];

        // Merge BukanGuru, Pensiun, Meninggal into "Tidak Layak"
        $notEligibleCount = 0;
        foreach (StatusPPG::cases() as $status) {
            if ($status->isNotEligible()) {
                $notEligibleCount += $counts[$status->value] ?? 0;
            }
        }
        $stats[] = Stat::make('Meninggal, Pensiun, Bukan Guru, Tidak AKtif', $notEligibleCount)
            ->color('danger');

        // Count remaining statuses
        $eligibleStatuses = [
            StatusPPG::BelumS1,
            StatusPPG::SudahSerdik,
            StatusPPG::SementaraSerdik,
            StatusPPG::BelumSerdik,
        ];
        foreach ($eligibleStatuses as $status) {
            $count = $counts[$status->value] ?? 0;
            $stats[] = Stat::make($status->getLabel(), $count)
                ->color($status->getColor());
        }

        // Count null/Tidak Diisi
        $nullCount = $counts[null] ?? 0;
        if ($nullCount > 0) {
            $stats[] = Stat::make('Tidak Diisi', $nullCount)
                ->color('gray');
        }

        return $stats;
    }

    private function isProvinsiScope(): bool
    {
        $kabKota = $this->getAuthenticatedWhitelistKabKota();

        if (is_string($kabKota) && str_contains(strtolower($kabKota), 'provinsi')) {
            return true;
        }

        $scopeLabel = $this->getScopeLabelForAuthenticatedUser();

        return $scopeLabel !== null && str_contains(strtolower($scopeLabel), 'provinsi');
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

    private function getScopeLabelForAuthenticatedUser(): ?string
    {
        $whitelist = $this->getAuthenticatedWhitelist();

        $whitelistScope = $whitelist?->instansi;

        if (is_string($whitelistScope) && $whitelistScope !== '') {
            return $whitelistScope;
        }

        return null;
    }
}
