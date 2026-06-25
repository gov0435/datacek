<?php

namespace App\Helpers;

class LptkData
{
    /**
     * Get list of LPTK data from JSON file
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
    public static function all(): array
    {
        $path = base_path('database/data/lptk.json');

        if (! file_exists($path)) {
            return [];
        }

        $json = file_get_contents($path);

        return json_decode($json, true) ?? [];
    }
}
