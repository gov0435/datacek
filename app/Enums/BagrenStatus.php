<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BagrenStatus: string implements HasColor, HasLabel
{
    case Antri = 'Antri';
    case Gagal = 'Gagal';
    case Berhasil = 'Berhasil';
    case Kosong = '__null__';

    public function getLabel(): string
    {
        return match ($this) {
            self::Antri => 'Antri',
            self::Gagal => 'Gagal',
            self::Berhasil => 'Berhasil',
            self::Kosong => 'Kosong',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Antri => 'warning',
            self::Gagal => 'danger',
            self::Berhasil => 'success',
            self::Kosong => 'gray',
        };
    }
}
