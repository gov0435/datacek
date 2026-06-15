<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisDokumenDinas: string implements HasLabel
{
    case BeritaAcara = 'berita_acara';
    case DokumenLain = 'dokumen_lain';

    public function getLabel(): string
    {
        return match ($this) {
            self::BeritaAcara => 'Berita Acara',
            self::DokumenLain => 'Dokumen Lain',
        };
    }
}
