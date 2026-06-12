<?php

use App\Models\SurveyPpg;
use Illuminate\Support\Facades\Schema;

// NOTE: The `ppg`-related migrations in this project are unguarded and assume the
// table already exists on Neon, so RefreshDatabase cannot run here. This test owns
// its single table directly on the sqlite :memory: connection instead.
beforeEach(function () {
    Schema::dropIfExists('survey_ppg');
    Schema::create('survey_ppg', function ($table) {
        $table->bigInteger('ptk_id')->unique();
        $table->boolean('has_potensi')->nullable();
        $table->string('nama')->nullable();
        $table->string('nuptk')->nullable();
        $table->boolean('is_guru_dapodik')->nullable();
        $table->boolean('verval_is_kasek')->nullable();
        $table->text('keterangan')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('survey_ppg');
});

function createMergedCsv(string $content): string
{
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'merged_test_'.uniqid().'.csv';
    file_put_contents($path, $content);

    return $path;
}

it('inserts new rows, preserves leading zeros and normalizes booleans', function () {
    $path = createMergedCsv(
        "\xEF\xBB\xBFptk_id,nama,nuptk,has_potensi,is_guru_dapodik,verval_is_kasek\n"
        ."201500073659,ISNA H. JANTU,0455759661210042,1,True,False\n"
    );

    $this->artisan('app:import-merged-dataset', ['file' => $path])->assertSuccessful();
    unlink($path);

    $record = SurveyPpg::find(201500073659);

    expect($record)->not->toBeNull()
        ->and($record->nama)->toBe('ISNA H. JANTU')
        ->and($record->nuptk)->toBe('0455759661210042')
        ->and($record->has_potensi)->toBeTrue()
        ->and($record->is_guru_dapodik)->toBeTrue()
        ->and($record->verval_is_kasek)->toBeFalse();
});

it('preserves keterangan on re-import', function () {
    SurveyPpg::query()->insert([
        'ptk_id' => 201500085625,
        'nama' => 'Original Name',
        'keterangan' => 'Catatan manual penting',
    ]);

    $path = createMergedCsv("ptk_id,nama,nuptk\n201500085625,Updated Name,0635748649300022\n");

    $this->artisan('app:import-merged-dataset', ['file' => $path])->assertSuccessful();
    unlink($path);

    $record = SurveyPpg::find(201500085625);

    expect($record->nama)->toBe('Updated Name')
        ->and($record->nuptk)->toBe('0635748649300022')
        ->and($record->keterangan)->toBe('Catatan manual penting');
});

it('upserts existing ptk_id without duplicating', function () {
    $path = createMergedCsv("ptk_id,nama\n111,First\n");
    $this->artisan('app:import-merged-dataset', ['file' => $path])->assertSuccessful();
    unlink($path);

    $path = createMergedCsv("ptk_id,nama\n111,Second\n");
    $this->artisan('app:import-merged-dataset', ['file' => $path])->assertSuccessful();
    unlink($path);

    expect(SurveyPpg::count())->toBe(1)
        ->and(SurveyPpg::find(111)->nama)->toBe('Second');
});

it('skips rows without ptk_id', function () {
    $path = createMergedCsv("ptk_id,nama\n,No ID User\n");

    $this->artisan('app:import-merged-dataset', ['file' => $path])->assertSuccessful();
    unlink($path);

    expect(SurveyPpg::count())->toBe(0);
});

it('does not write in dry-run mode', function () {
    $path = createMergedCsv("ptk_id,nama\n999,Dry Run User\n");

    $this->artisan('app:import-merged-dataset', ['file' => $path, '--dry-run' => true])->assertSuccessful();
    unlink($path);

    expect(SurveyPpg::find(999))->toBeNull();
});
