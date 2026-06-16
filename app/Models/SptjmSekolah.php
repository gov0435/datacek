<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SptjmSekolah extends Model
{
    protected $table = 'sptjm_sekolah';

    protected $fillable = [
        'sekolah_npsn',
        'sekolah_nama',
        'sekolah_jenjang',
        'sekolah_kota',
        'sekolah_propinsi',
        'scope',
        'jumlah_guru',
        'generated_by',
        'is_valid',
    ];

    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
        ];
    }

    public function unggahan(): HasMany
    {
        return $this->hasMany(SptjmUnggahan::class, 'sptjm_sekolah_id');
    }

    public function unggahanValid(): HasOne
    {
        return $this->hasOne(SptjmUnggahan::class, 'sptjm_sekolah_id')
            ->latestOfMany();
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
