<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum StatusDaftar: string implements HasColor, HasLabel
{
    case BelumDaftar = 'Belum Daftar';
    case SudahDaftar = 'Sudah Daftar';

    public function getLabel(): string|Htmlable|null
    {
        return $this->value;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SudahDaftar => 'success',
            self::BelumDaftar => 'warning',
        };
    }
}
