<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusPPG: string implements HasColor, HasLabel
{
    case BelumS1 = 'belum_s1';
    case BukanGuru = 'bukan_guru';
    case Meninggal = 'meninggal';
    case TidakAktif = 'tidak_aktif';
    case Pensiun = 'pensiun';
    case SudahSerdik = 'sudah_serdik';
    case SementaraSerdik = 'sementara_serdik';
    case BelumSerdik = 'belum_serdik';

    public function getLabel(): string
    {
        return match ($this) {
            self::SementaraSerdik => 'Sementara Serdik',
            self::BelumS1 => 'Belum S1',
            self::BukanGuru => 'Bukan Guru',
            self::Meninggal => 'Meninggal',
            self::TidakAktif => 'Tidak Aktif',
            self::Pensiun => 'Pensiun',
            self::SudahSerdik => 'Sudah Serdik',
            self::BelumSerdik => 'Belum Serdik',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::SementaraSerdik => 'info',
            self::BelumS1 => 'gray',
            self::BukanGuru => 'danger',
            self::Meninggal => 'danger',
            self::TidakAktif => 'danger',
            self::Pensiun => 'danger',
            self::SudahSerdik => 'success',
            self::BelumSerdik => 'warning',
        };
    }

    public function isNotEligible(): bool
    {
        return match ($this) {
            self::BukanGuru, self::Pensiun, self::Meninggal => true, self::TidakAktif => true,
            default => false,
        };
    }
}
