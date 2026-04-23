<?php

namespace App\Console\Commands;

use App\Models\PotensiPpg;
use Illuminate\Console\Command;

class ImportDataSeleksi extends Command
{
    protected $signature = 'app:import-data-seleksi {--dry-run}';

    protected $description = 'Import data seleksi from CSV into ppg table';

    private const FILE_PATH = 'repos/data/data_baru.csv';

    // CSV Header → DB Column mapping
    private const COLUMN_MAP = [
        'Ptk ID' => 'ptk_id',
        'Nama' => 'nama',
        'Status Sekolah' => 'status_sekolah',
        'Sta Jenjang' => 'sta_jenjang',
        'Sta Asn' => 'sta_asn',
        'Keterangan Jabatan' => 'keterangan_jabatan',
        'Sta Sekolah' => 'sta_sekolah',
        'Sta Ijazah' => 'sta_ijazah',
        'Sta Kandidat' => 'sta_kandidat',
        'Tahun' => 'tahun',
        'Gelombang' => 'gelombang',
        'Periode' => 'periode',
        'Nuptk' => 'nuptk',
        'Nik' => 'nik',
        'Nip' => 'nip',
        'Status Validasi Nik' => 'status_validasi_nik',
        'Ptk Foto' => 'ptk_foto',
        'No Telp' => 'no_telp',
        'Email Belajar ID' => 'email_belajar_id',
        'Status Tautan Belajar' => 'status_tautan_belajar',
        'Alamat Surel' => 'alamat_surel',
        'Alamat Provinsi' => 'alamat_provinsi',
        'Alamat Kota' => 'alamat_kota',
        'Pegawai' => 'pegawai',
        'Jenis Ptk' => 'jenis_ptk',
        'Npsn' => 'npsn',
        'Naungan' => 'naungan',
        'Jenis Sekolah' => 'jenis_sekolah',
        'Jenjang' => 'jenjang',
        'Kota' => 'kota',
        'Status Verval Ijazah' => 'status_verval_ijazah',
        'Jenis Verval Ijazah' => 'jenis_verval_ijazah',
        'Bidang Studi Ppg' => 'bidang_studi_ppg',
        'Waktu Mulai Daftar' => 'waktu_mulai_daftar',
        'Status Ajuan' => 'status_ajuan',
        'Status Biodata' => 'status_biodata',
        'Status Bidang Studi Ppg' => 'status_bidang_studi_ppg',
        'Layak Daftar' => 'layak_daftar',
        'Keberminatan Status' => 'keberminatan_status',
        'Status Daftar' => 'status_daftar',
    ];

    // Columns to update when record already exists
    private const UPDATE_COLUMNS = [
        'status_ajuan',
        'layak_daftar',
        'keberminatan_status',
        'status_daftar',
    ];

    public function handle(): int
    {
        $filePath = base_path(self::FILE_PATH);
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

        $rows = [];
        $skipped = 0;

        while ($row = fgetcsv($file)) {
            if (count($headers) !== count($row)) {
                $skipped++;

                continue;
            }

            $csvData = array_combine($headers, $row);
            $mapped = $this->mapRow($csvData);

            if (empty($mapped['ptk_id'])) {
                $skipped++;

                continue;
            }

            $mapped = array_map(fn ($v) => $v === '' ? null : $v, $mapped);
            $mapped = $this->castNumericFields($mapped);
            $rows[$mapped['ptk_id']] = $mapped;
        }

        $rows = array_values($rows);

        fclose($file);

        $this->info($dryRun ? '[DRY RUN] Simulating import...' : 'Starting import...');
        $this->info('Total rows parsed: '.count($rows));

        if ($dryRun) {
            $this->info('[DRY RUN] Would upsert '.count($rows).' rows. Skipped: '.$skipped);

            return self::SUCCESS;
        }

        $chunks = array_chunk($rows, 250);
        $bar = $this->output->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $chunk) {
            PotensiPpg::upsert($chunk, ['ptk_id'], self::UPDATE_COLUMNS);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Upserted: '.count($rows));
        $this->info('Skipped: '.$skipped);

        return self::SUCCESS;
    }

    private function mapRow(array $csvData): array
    {
        $mapped = [];
        foreach (self::COLUMN_MAP as $csvHeader => $dbColumn) {
            if (array_key_exists($csvHeader, $csvData)) {
                $mapped[$dbColumn] = $csvData[$csvHeader];
            }
        }

        return $mapped;
    }

    private function castNumericFields(array $data): array
    {
        $intFields = ['ptk_id', 'tahun', 'nik'];
        $floatFields = ['gelombang', 'nuptk', 'nip'];

        foreach ($intFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }
        foreach ($floatFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (float) $data[$field];
            }
        }

        return $data;
    }
}
