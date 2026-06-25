<?php

namespace App\Filament\Pages;

use App\Helpers\LptkData;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Lptk extends Page
{
    protected static ?string $title = 'LPTK';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected ?string $heading = 'LPTK PPG Guter Tahap 2';

    protected ?string $subheading = 'Update: 26 Juni 2026 06:59';

    protected string $view = 'filament.pages.lptk';

    public string $search = '';

    /**
     * Get filtered LPTK data
     *
     * @return array<int, array{
     *     no: int,
     *     nama_lptk: string,
     *     jumlah_calon_peserta: int,
     *     website_lptk: string,
     *     link_lapor_diri: string,
     *     link_grup: string
     * }>
     */
    public function getLptkData(): array
    {
        $all = LptkData::all();

        if (empty(trim($this->search))) {
            return $all;
        }

        $search = strtolower(trim($this->search));

        return array_filter($all, function ($lptk) use ($search) {
            $groupMatches = false;
            if (is_array($lptk['link_grup'])) {
                foreach ($lptk['link_grup'] as $group) {
                    if (str_contains(strtolower($group['nama'] ?? ''), $search) || str_contains(strtolower($group['url'] ?? ''), $search)) {
                        $groupMatches = true;
                        break;
                    }
                }
            } elseif (is_string($lptk['link_grup'])) {
                $groupMatches = str_contains(strtolower($lptk['link_grup']), $search);
            }

            return str_contains(strtolower($lptk['nama_lptk']), $search) ||
                str_contains(strtolower($lptk['website_lptk']), $search) ||
                str_contains(strtolower($lptk['link_lapor_diri']), $search) ||
                $groupMatches;
        });
    }
}
