<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SptjmUnggahan extends Model
{
    protected $table = 'sptjm_unggahan';

    protected $fillable = [
        'sptjm_sekolah_id',
        'disk',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'catatan',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function sptjmSekolah(): BelongsTo
    {
        return $this->belongsTo(SptjmSekolah::class, 'sptjm_sekolah_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
