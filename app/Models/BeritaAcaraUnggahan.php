<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeritaAcaraUnggahan extends Model
{
    protected $table = 'berita_acara_unggahan';

    protected $fillable = [
        'berita_acara_sekolah_id',
        'disk',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'is_valid',
        'catatan',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_valid' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function beritaAcaraSekolah(): BelongsTo
    {
        return $this->belongsTo(BeritaAcaraSekolah::class, 'berita_acara_sekolah_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
