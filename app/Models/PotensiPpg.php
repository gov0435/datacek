<?php

namespace App\Models;

use App\Enums\Jenjang;
use App\Enums\StatusDaftar;
use App\Enums\StatusPPG;
use Illuminate\Database\Eloquent\Model;

class PotensiPpg extends Model
{
    protected $table = 'ppg';

    protected $primaryKey = 'ptk_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'is_serdik',
        'statusppg',
    ];

    protected function casts(): array
    {
        return [
            'ptk_id' => 'integer',
            'tahun' => 'integer',
            'nik' => 'integer',
            'gelombang' => 'float',
            'nuptk' => 'float',
            'nip' => 'float',
            'is_check' => 'boolean',
            'is_serdik' => 'boolean',
            'jenjang' => Jenjang::class,
            'status_daftar' => StatusDaftar::class,
            'statusppg' => StatusPPG::class,
        ];
    }
}
