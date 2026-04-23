<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum LayakDaftar: string implements HasColor, HasLabel
{
    case Layak = 'Layak Daftar';
    case TidakLayak = 'Tidak Layak Daftar';

    public function getLabel(): string|Htmlable|null
    {
        return $this->value;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Layak => 'success',
            self::TidakLayak => 'danger',
        };
    }
}
