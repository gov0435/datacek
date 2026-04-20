<?php

namespace App\Models;

use App\Enums\KabKota;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'nama', 'instansi', 'kabkota'])]
class Whitelist extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kabkota' => KabKota::class,
        ];
    }
}
