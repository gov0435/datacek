<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyPpg extends Model
{
    protected $table = 'survey_ppg';

    protected $primaryKey = 'ptk_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ptk_id' => 'integer',
            'has_potensi' => 'boolean',
            'has_peserta' => 'boolean',
            'has_verval' => 'boolean',
            'is_guru_dapodik' => 'boolean',
            'verval_is_lapor' => 'boolean',
            'verval_is_undur' => 'boolean',
            'verval_is_peserta' => 'boolean',
            'verval_is_cadangan' => 'boolean',
            'verval_is_plpg' => 'boolean',
            'verval_is_kasek' => 'boolean',
            'verval_is_lengkap_pks' => 'boolean',
            'verval_is_lengkap_laporan' => 'boolean',
            'verval_is_epks' => 'boolean',
            'verval_kandidat_is_lulus' => 'boolean',
            'verval_kandidat_skor_total_final' => 'float',
        ];
    }
}
