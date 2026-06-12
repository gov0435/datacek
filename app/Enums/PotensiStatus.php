<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PotensiStatus: string implements HasColor, HasLabel
{
    case Berminat = 'Berminat';
    case SedangPPG = 'Sedang PPG';
    case SudahBerserdik = 'Sudah Berserdik';
    case TidakBerminat = 'Tidak Berminat';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Berminat => 'success',
            self::SedangPPG => 'info',
            self::SudahBerserdik => 'gray',
            self::TidakBerminat => 'danger',
        };
    }
}
