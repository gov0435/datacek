<?php

namespace App\Models;

use App\Enums\JenisDokumenDinas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenDinas extends Model
{
    protected $table = 'dokumen_dinas';

    protected $fillable = [
        'kabkota',
        'jenis',
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
            'jenis' => JenisDokumenDinas::class,
            'is_valid' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
