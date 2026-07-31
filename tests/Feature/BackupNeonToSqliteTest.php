<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

it('backups database to sqlite file in /db folder', function (): void {
    $testFileName = 'test_neon_data.sqlite';
    $targetPath = base_path("db/{$testFileName}");

    if (File::exists($targetPath)) {
        File::delete($targetPath);
    }

    $this->artisan('db:backup-neon-sqlite', [
        '--file' => $testFileName,
        '--from-connection' => config('database.default'),
    ])
        ->assertExitCode(0);

    expect(File::exists($targetPath))->toBeTrue();

    // Verify sqlite file can be queried
    config(['database.connections.sqlite_test_read' => [
        'driver' => 'sqlite',
        'database' => $targetPath,
        'prefix' => '',
    ]]);

    DB::purge('sqlite_test_read');

    $tables = Schema::connection('sqlite_test_read')->getTableListing();
    expect($tables)->toBeArray();

    // Cleanup
    File::delete($targetPath);
});
