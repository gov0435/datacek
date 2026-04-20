<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum Jenjang: string implements HasColor, HasLabel
{
    case Lainnya = 'Lainnya';
    case Paud = 'PAUD';
    case Sd = 'SD';
    case Slb = 'SLB';
    case Sma = 'SMA';
    case Smk = 'SMK';
    case Smp = 'SMP';

    public function getLabel(): string|Htmlable|null
    {
        return $this->value;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Paud => 'success',
            self::Sd => 'info',
            self::Slb => 'warning',
            self::Sma => 'primary',
            self::Smk => 'danger',
            self::Smp => 'gray',
            default => 'secondary',
        };
    }
}
