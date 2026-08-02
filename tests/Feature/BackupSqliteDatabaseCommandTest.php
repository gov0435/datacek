<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

it('creates hot snapshot vacuum into, compresses with gzip, and uploads sqlite backup to s3 with new path structure', function () {
    // Setup temporary test sqlite database
    $testDbDir = storage_path('framework/testing');
    if (! File::exists($testDbDir)) {
        File::makeDirectory($testDbDir, 0755, true);
    }
    $testDbPath = "{$testDbDir}/test_database.sqlite";
    File::put($testDbPath, '');

    $pdo = new PDO("sqlite:{$testDbPath}");
    $pdo->exec('CREATE TABLE dummy (id INTEGER PRIMARY KEY, name TEXT);');
    $pdo->exec("INSERT INTO dummy (name) VALUES ('Test Item 1'), ('Test Item 2');");
    unset($pdo);

    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.driver', 'sqlite');
    Config::set('database.connections.sqlite.database', $testDbPath);

    $this->artisan('db:backup-sqlite --disk=s3')
        ->assertExitCode(0);

    // Verify S3 files in backup/kawal-ppg
    $files = Storage::disk('s3')->allFiles('backup/kawal-ppg');
    expect($files)->not->toBeEmpty();

    $gzFiles = array_filter($files, fn (string $f): bool => str_ends_with($f, '.sqlite.gz'));
    $jsonFiles = array_filter($files, fn (string $f): bool => str_ends_with($f, '.json'));

    expect($gzFiles)->toHaveCount(1);
    expect($jsonFiles)->toHaveCount(1);

    $gzPath = array_values($gzFiles)[0];
    $jsonPath = array_values($jsonFiles)[0];

    // Verify path structure: backup/kawal-ppg/YYYY-MM-DD/HHMMSS-database.sqlite.gz
    $todayStr = now()->format('Y-m-d');
    expect($gzPath)->toContain("backup/kawal-ppg/{$todayStr}/");
    expect($gzPath)->toEndWith('-database.sqlite.gz');
    expect($jsonPath)->toEndWith('-database.json');

    $manifestContent = Storage::disk('s3')->get($jsonPath);
    $manifest = json_decode($manifestContent, true);

    expect($manifest)->toHaveKeys([
        'filename',
        'timestamp',
        'compressed_bytes',
        'uncompressed_bytes',
        'sha256',
        'compressed',
        'compression_algorithm',
        'app_version',
        'laravel_version',
        'retention_days',
    ]);
    expect($manifest['compressed'])->toBeTrue();
    expect($manifest['compression_algorithm'])->toBe('gzip');
    expect($manifest['retention_days'])->toBe(7);
    expect($manifest['compressed_bytes'])->toBe(Storage::disk('s3')->size($gzPath));

    // Verify local cleanup
    $tempDir = storage_path('app/private/sqlite-backups');
    if (File::exists($tempDir)) {
        $tempFiles = File::files($tempDir);
        expect($tempFiles)->toBeEmpty();
    }

    // Clean up test DB
    if (File::exists($testDbPath)) {
        File::delete($testDbPath);
    }
});

it('fails backup when default database driver is not sqlite', function () {
    Config::set('database.default', 'pgsql');
    Config::set('database.connections.pgsql.driver', 'pgsql');

    $this->artisan('db:backup-sqlite')
        ->assertExitCode(1);
});

it('fails backup when sqlite database file does not exist', function () {
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.driver', 'sqlite');
    Config::set('database.connections.sqlite.database', 'non_existent_db_path.sqlite');

    $this->artisan('db:backup-sqlite')
        ->assertExitCode(1);
});
