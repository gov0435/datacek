<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

class BackupSqliteDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup-sqlite
                            {--disk=s3 : The storage disk to upload backup files}
                            {--keep-local : Keep temporary local snapshot file after upload}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup SQLite database to S3-compatible Object Storage using native VACUUM INTO snapshot and Gzip compression';

    public function handle(): int
    {
        $diskName = (string) $this->option('disk');
        $keepLocal = (bool) $this->option('keep-local');

        $this->info("Starting SQLite database backup to disk [{$diskName}]...");

        // 1. Pre-flight Checks
        $defaultConnection = (string) Config::get('database.default', 'sqlite');
        $driver = DB::connection($defaultConnection)->getDriverName();

        if ($driver !== 'sqlite') {
            $this->error("Backup aborted: Current database driver for [{$defaultConnection}] is [{$driver}], expected [sqlite].");

            return self::FAILURE;
        }

        $dbConfigPath = (string) Config::get("database.connections.{$defaultConnection}.database");
        $dbPath = str_starts_with($dbConfigPath, '/') || str_contains($dbConfigPath, ':')
            ? $dbConfigPath
            : base_path($dbConfigPath);

        if (! File::exists($dbPath) || ! is_readable($dbPath)) {
            $this->error("Backup aborted: SQLite database file not found or not readable at [{$dbPath}].");

            return self::FAILURE;
        }

        $dbSizeBytes = File::size($dbPath);
        $tempDir = storage_path('app/private/sqlite-backups');

        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $freeSpace = @disk_free_space(dirname($tempDir));
        if ($freeSpace !== false && $freeSpace < ($dbSizeBytes * 1.5)) {
            $this->error('Backup aborted: Insufficient disk space for snapshot creation. Required: ~'.number_format($dbSizeBytes * 1.5).' bytes, Available: '.number_format($freeSpace).' bytes.');

            return self::FAILURE;
        }

        $now = now();
        $randomHash = Str::random(6);
        $tempBase = "database-{$now->format('YmdHis')}-{$randomHash}";
        $localSqlitePath = "{$tempDir}/{$tempBase}.sqlite";
        $localGzPath = "{$tempDir}/{$tempBase}.sqlite.gz";
        $localJsonPath = "{$tempDir}/{$tempBase}.json";

        $remoteDir = 'backup/kawal-ppg/'.$now->format('Y-m-d');
        $fileBasename = $now->format('His').'-database';
        $s3GzPath = "{$remoteDir}/{$fileBasename}.sqlite.gz";
        $s3JsonPath = "{$remoteDir}/{$fileBasename}.json";

        try {
            // 2. Snapshot Creation (VACUUM INTO)
            $this->info("Creating hot snapshot using VACUUM INTO at [{$localSqlitePath}]...");
            if (File::exists($localSqlitePath)) {
                File::delete($localSqlitePath);
            }

            $pdo = DB::connection($defaultConnection)->getPdo();
            // SQLite VACUUM INTO does not support parameterized binding for the file path
            $escapedPath = str_replace("'", "''", $localSqlitePath);
            $pdo->exec("VACUUM INTO '{$escapedPath}'");

            if (! File::exists($localSqlitePath)) {
                throw new RuntimeException("Snapshot file was not created at [{$localSqlitePath}].");
            }

            // 3. Integrity Check
            $this->info('Performing PRAGMA integrity_check on snapshot...');
            $snapshotPdo = new PDO("sqlite:{$localSqlitePath}");
            $snapshotPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $integrityResult = $snapshotPdo->query('PRAGMA integrity_check;')->fetchColumn();
            unset($snapshotPdo);

            if (strtolower((string) $integrityResult) !== 'ok') {
                throw new RuntimeException("PRAGMA integrity_check failed with result: [{$integrityResult}].");
            }

            // 4. Gzip Compression
            $this->info("Compressing snapshot to Gzip at [{$localGzPath}]...");
            if (File::exists($localGzPath)) {
                File::delete($localGzPath);
            }

            $srcStream = fopen($localSqlitePath, 'rb');
            $gzStream = gzopen($localGzPath, 'wb9');

            if ($srcStream === false || $gzStream === false) {
                if ($srcStream !== false) {
                    fclose($srcStream);
                }
                if ($gzStream !== false) {
                    gzclose($gzStream);
                }
                throw new RuntimeException('Failed to initialize stream for Gzip compression.');
            }

            while (! feof($srcStream)) {
                $buffer = fread($srcStream, 65536);
                if ($buffer !== false && $buffer !== '') {
                    gzwrite($gzStream, $buffer);
                }
            }

            fclose($srcStream);
            gzclose($gzStream);

            if (! File::exists($localGzPath)) {
                throw new RuntimeException("Gzip file was not created at [{$localGzPath}].");
            }

            // 5. Hash & Metadata Calculation
            $compressedBytes = File::size($localGzPath);
            $uncompressedBytes = File::size($localSqlitePath);
            $sha256 = hash_file('sha256', $localGzPath);

            $manifest = [
                'filename' => "{$fileBasename}.sqlite.gz",
                'timestamp' => $now->toIso8601String(),
                'compressed_bytes' => $compressedBytes,
                'uncompressed_bytes' => $uncompressedBytes,
                'sha256' => $sha256,
                'compressed' => true,
                'compression_algorithm' => 'gzip',
                'app_version' => config('app.version', '1.0.0'),
                'laravel_version' => app()->version(),
                'retention_days' => 7,
            ];

            File::put($localJsonPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // 6. Streaming Upload to S3
            $this->info("Streaming compressed snapshot upload to disk [{$diskName}] path [{$s3GzPath}]...");
            $uploadStream = fopen($localGzPath, 'rb');
            if ($uploadStream === false) {
                throw new RuntimeException("Failed to open stream for Gzip file [{$localGzPath}].");
            }

            $uploadSuccess = Storage::disk($diskName)->writeStream($s3GzPath, $uploadStream);
            if (is_resource($uploadStream)) {
                fclose($uploadStream);
            }

            if (! $uploadSuccess) {
                throw new RuntimeException("Failed to upload Gzip snapshot stream to disk [{$diskName}].");
            }

            $manifestSuccess = Storage::disk($diskName)->put($s3JsonPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            if (! $manifestSuccess) {
                throw new RuntimeException("Failed to upload manifest JSON to disk [{$diskName}].");
            }

            // 7. Verification
            $this->info("Verifying uploaded object on disk [{$diskName}]...");
            if (! Storage::disk($diskName)->exists($s3GzPath)) {
                throw new RuntimeException("Verification failed: S3 Gzip object [{$s3GzPath}] does not exist.");
            }

            $remoteBytes = Storage::disk($diskName)->size($s3GzPath);
            if ($remoteBytes !== $compressedBytes) {
                throw new RuntimeException("Verification failed: Size mismatch for [{$s3GzPath}]. Local: {$compressedBytes} bytes, Remote: {$remoteBytes} bytes.");
            }

            if (! Storage::disk($diskName)->exists($s3JsonPath)) {
                throw new RuntimeException("Verification failed: S3 manifest object [{$s3JsonPath}] does not exist.");
            }

            $this->info("Backup successfully completed and verified on disk [{$diskName}]. Compressed size: ".number_format($compressedBytes).' bytes.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            Log::error("BackupSqliteDatabaseCommand failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return self::FAILURE;
        } finally {
            // 8. Safety Cleanup
            if (! $keepLocal) {
                if (File::exists($localSqlitePath)) {
                    File::delete($localSqlitePath);
                }
                if (File::exists($localGzPath)) {
                    File::delete($localGzPath);
                }
                if (File::exists($localJsonPath)) {
                    File::delete($localJsonPath);
                }
            }
        }
    }
}
