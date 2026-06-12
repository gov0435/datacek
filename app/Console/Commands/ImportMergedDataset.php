<?php

namespace App\Console\Commands;

use App\Models\SurveyPpg;
use Illuminate\Console\Command;

class ImportMergedDataset extends Command
{
    protected $signature = 'app:import-merged-dataset {file?} {--dry-run}';

    protected $description = 'Import merged_3_dataset.csv into survey_ppg table';

    private const FILE_PATH = 'repos/py/merged_3_dataset.csv';

    /**
     * Columns stored as PostgreSQL boolean. CSV uses a mix of 0/1 and True/False.
     */
    private const BOOLEAN_FIELDS = [
        'has_potensi',
        'has_peserta',
        'has_verval',
        'is_guru_dapodik',
        'verval_is_lapor',
        'verval_is_undur',
        'verval_is_peserta',
        'verval_is_cadangan',
        'verval_is_plpg',
        'verval_is_kasek',
        'verval_is_lengkap_pks',
        'verval_is_lengkap_laporan',
        'verval_is_epks',
        'verval_kandidat_is_lulus',
    ];

    /**
     * Numeric IDs stored as BIGINT.
     */
    private const INT_FIELDS = [
        'ptk_id',
        'potensi_ppgdj_keberminatan_id',
        'potensi_instansi_id',
        'potensi_akun_id',
        'peserta_id',
        'peserta_ppgdj_mhs_id',
        'verval_ppgdj_mhs_id',
    ];

    private const FLOAT_FIELDS = [
        'verval_kandidat_skor_total_final',
    ];

    /**
     * Columns filled manually and never touched by the import.
     */
    private const PROTECTED_COLUMNS = [
        'keterangan',
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file') ?? base_path(self::FILE_PATH);
        $dryRun = $this->option('dry-run');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);

        if (! $headers) {
            $this->error('Failed to read CSV headers.');
            fclose($file);

            return self::FAILURE;
        }

        // Strip UTF-8 BOM from the first header (file is utf-8-sig).
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        $rows = [];
        $skipped = 0;

        while ($row = fgetcsv($file)) {
            if (count($headers) !== count($row)) {
                $skipped++;

                continue;
            }

            $data = array_combine($headers, $row);

            // Manual columns must never be sourced from the CSV.
            foreach (self::PROTECTED_COLUMNS as $column) {
                unset($data[$column]);
            }

            $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

            if (empty($data['ptk_id'])) {
                $skipped++;

                continue;
            }

            $data = $this->castFields($data);
            $rows[$data['ptk_id']] = $data;
        }

        $rows = array_values($rows);
        fclose($file);

        $this->info($dryRun ? '[DRY RUN] Simulating import...' : 'Starting import...');
        $this->info('Total rows parsed: '.count($rows));

        if ($dryRun) {
            $this->info('[DRY RUN] Would upsert '.count($rows).' rows. Skipped: '.$skipped);

            return self::SUCCESS;
        }

        if ($rows === []) {
            $this->warn('No rows to import.');

            return self::SUCCESS;
        }

        // Refresh every CSV column on conflict, except the key (and the manual
        // columns, which are already absent from the payload).
        $updateColumns = array_values(array_diff(array_keys($rows[0]), ['ptk_id']));

        $chunks = array_chunk($rows, 250);
        $bar = $this->output->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $chunk) {
            SurveyPpg::upsert($chunk, ['ptk_id'], $updateColumns);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Upserted: '.count($rows));
        $this->info('Skipped: '.$skipped);

        return self::SUCCESS;
    }

    private function castFields(array $data): array
    {
        foreach (self::INT_FIELDS as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        foreach (self::FLOAT_FIELDS as $field) {
            if (isset($data[$field])) {
                $data[$field] = (float) $data[$field];
            }
        }

        foreach (self::BOOLEAN_FIELDS as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->toBool($data[$field]);
            }
        }

        return $data;
    }

    private function toBool(string $value): bool
    {
        return in_array(strtolower($value), ['1', 'true', 't', 'yes'], true);
    }
}
