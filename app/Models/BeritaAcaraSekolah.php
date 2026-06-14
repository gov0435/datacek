<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BeritaAcaraSekolah extends Model
{
    protected $table = 'berita_acara_sekolah';

    protected $fillable = [
        'sekolah_npsn',
        'sekolah_nama',
        'sekolah_jenjang',
        'sekolah_kota',
        'sekolah_propinsi',
        'scope',
        'jumlah_guru',
        'generated_by',
    ];

    public function unggahan(): HasMany
    {
        return $this->hasMany(BeritaAcaraUnggahan::class, 'berita_acara_sekolah_id');
    }

    public function unggahanValid(): HasOne
    {
        return $this->hasOne(BeritaAcaraUnggahan::class, 'berita_acara_sekolah_id')
            ->where('is_valid', true)
            ->latestOfMany();
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
