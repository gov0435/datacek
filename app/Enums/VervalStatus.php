<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VervalStatus: string implements HasColor, HasLabel
{
    case Disetujui = 'Ajuan Disetujui';
    case SudahAjuan = 'Pendaftar Sudah Ajuan';

    public function getLabel(): string
    {
        return $this->value;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Disetujui => 'success',
            self::SudahAjuan => 'warning',
        };
    }
}
