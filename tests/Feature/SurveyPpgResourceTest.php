<?php

use App\Filament\Resources\SurveyPpgs\SurveyPpgResource;
use App\Models\SurveyPpg;

test('survey ppg resource is read only list page', function () {
    expect(SurveyPpgResource::getNavigationLabel())->toBe('Survey PPG')
        ->and(array_keys(SurveyPpgResource::getPages()))->toBe(['index']);
});

test('survey ppg model uses ptk id and casts resource fields', function () {
    $surveyPpg = new SurveyPpg;

    expect($surveyPpg->getTable())->toBe('survey_ppg')
        ->and($surveyPpg->getKeyName())->toBe('ptk_id')
        ->and($surveyPpg->getIncrementing())->toBeFalse()
        ->and($surveyPpg->getCasts())->toMatchArray([
            'ptk_id' => 'integer',
            'has_potensi' => 'boolean',
            'has_peserta' => 'boolean',
            'has_verval' => 'boolean',
            'peserta_layak_daftar' => 'App\Enums\LayakDaftar',
            'potensi_status' => 'App\Enums\PotensiStatus',
            'potensi_waktu' => 'datetime',
            'peserta_keberminatan_waktu' => 'datetime',
            'verval_status' => 'App\Enums\VervalStatus',
            'verval_wkt_ajuan' => 'datetime',
            'verval_tgl_lahir' => 'date',
            'verval_kandidat_skor_total_final' => 'float',
        ]);
});
