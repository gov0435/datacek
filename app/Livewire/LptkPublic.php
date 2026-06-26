<?php

namespace App\Livewire;

use App\Helpers\LptkData;
use Livewire\Component;

class LptkPublic extends Component
{
    public string $search = '';

    public function render()
    {
        $all = LptkData::all();

        $filtered = $this->search
            ? array_filter($all, fn ($lptk) => $this->matchesSearch($lptk))
            : $all;

        return view('livewire.lptk-public', [
            'lptkList' => $filtered,
            'totalLptk' => count($filtered),
            'totalCalon' => array_sum(array_column($filtered, 'jumlah_calon_peserta')),
        ]);
    }

    private function matchesSearch(array $lptk): bool
    {
        $search = strtolower(trim($this->search));

        if (str_contains(strtolower($lptk['nama_lptk']), $search)) {
            return true;
        }

        if (str_contains(strtolower($lptk['website_lptk']), $search)) {
            return true;
        }

        if (str_contains(strtolower($lptk['link_lapor_diri']), $search)) {
            return true;
        }

        if (is_array($lptk['link_grup'])) {
            foreach ($lptk['link_grup'] as $group) {
                if (str_contains(strtolower($group['nama'] ?? ''), $search) || str_contains(strtolower($group['url'] ?? ''), $search)) {
                    return true;
                }
            }
        } elseif (is_string($lptk['link_grup'])) {
            if (str_contains(strtolower($lptk['link_grup']), $search)) {
                return true;
            }
        }

        return false;
    }
}
