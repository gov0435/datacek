<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisDokumenDinas: string implements HasLabel
{
    case BeritaAcara = 'Berita Acara';
    case DokumenLain = 'Dokumen Lain';

    public function getLabel(): string
    {
        return $this->value;
    }
}
