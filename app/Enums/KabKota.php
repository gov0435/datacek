<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum KabKota: string implements HasLabel
{
    case KabBoalemo = 'Kab. Boalemo';
    case KabBonebolango = 'Kab. Bonebolango';
    case KabGorontalo = 'Kab. Gorontalo';
    case KabGorontaloUtara = 'Kab. Gorontalo Utara';
    case KabPohuwato = 'Kab. Pohuwato';
    case KotaGorontalo = 'Kota Gorontalo';
    case Provinsi = 'Provinsi';

    public function getLabel(): string|Htmlable|null
    {
        return $this->value;
    }
}
