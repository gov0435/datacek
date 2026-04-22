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
    protected function getStats(): array
    {
        $query = PotensiPpg::query();

        // Filter by kabkota dari whitelist user yang login
        $kabKota = $this->getAuthenticatedWhitelistKabKota();

        if ($kabKota !== null) {
            $query->where('kota', $kabKota);
        }

        // Single query to get all counts
        $counts = $query
            ->get()
            ->groupBy('statusppg')
            ->map(fn ($group) => $group->count())
            ->all();

        $stats = [];

        // Count each enum status
        foreach (StatusPPG::cases() as $status) {
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
