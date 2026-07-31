<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class BackupNeonToSqliteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup-neon-sqlite
                            {--file=neon_data.sqlite : The output sqlite filename inside /db directory}
                            {--from-connection=pgsql : Source PostgreSQL connection name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup all tables and data from Neon PostgreSQL to local SQLite file in /db';

    /**
     * System tables to ignore during backup.
     *
     * @var array<int, string>
     */
    private const IGNORED_TABLES = [
        'spatial_ref_sys',
        'geography_columns',
        'geometry_columns',
        'raster_columns',
        'raster_overviews',
    ];

    public function handle(): int
    {
        $filename = (string) $this->option('file');
        $fromConnection = (string) $this->option('from-connection');

        $this->info("Starting backup from connection [{$fromConnection}] to SQLite [/db/{$filename}]...");

        $dbDir = base_path('db');
        if (! File::exists($dbDir)) {
            File::makeDirectory($dbDir, 0755, true);
            $this->info("Created directory: {$dbDir}");
        }

        $targetPath = $dbDir.DIRECTORY_SEPARATOR.$filename;

        // Test source connection
        try {
            DB::connection($fromConnection)->getPdo();
        } catch (Throwable $e) {
            $this->error("Failed to connect to source connection [{$fromConnection}]: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Recreate empty target SQLite file
        if (File::exists($targetPath)) {
            File::delete($targetPath);
        }
        File::put($targetPath, '');

        // Dynamically register SQLite backup connection
        Config::set('database.connections.sqlite_backup', [
            'driver' => 'sqlite',
            'database' => $targetPath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite_backup');

        $tables = Schema::connection($fromConnection)->getTableListing();

        // Filter out system tables
        $tables = array_values(array_filter(
            $tables,
            fn (string $table): bool => ! in_array(strtolower($table), self::IGNORED_TABLES, true)
        ));

        if ($tables === []) {
            $this->warn('No tables found in source database.');

            return self::SUCCESS;
        }

        $this->info('Found '.count($tables).' table(s) to backup.');

        DB::connection('sqlite_backup')->statement('PRAGMA foreign_keys = OFF;');

        $summary = [];

        foreach ($tables as $table) {
            $sourceTable = $table;
            $sqliteTable = str_contains($table, '.') ? Str::afterLast($table, '.') : $table;

            $this->comment("Processing table: {$sourceTable} -> {$sqliteTable}");

            try {
                $columns = Schema::connection($fromConnection)->getColumns($sourceTable);

                // Create table in SQLite
                Schema::connection('sqlite_backup')->dropIfExists($sqliteTable);
                Schema::connection('sqlite_backup')->create($sqliteTable, function (Blueprint $blueprint) use ($columns): void {
                    foreach ($columns as $column) {
                        $colName = $column['name'];
                        $typeName = strtolower((string) $column['type_name']);
                        $isNullable = (bool) ($column['nullable'] ?? true);

                        $colDef = match (true) {
                            str_contains($typeName, 'int') && ($column['auto_increment'] ?? false) => $blueprint->id($colName),
                            str_contains($typeName, 'bigint') => $blueprint->bigInteger($colName),
                            str_contains($typeName, 'int') => $blueprint->integer($colName),
                            str_contains($typeName, 'bool') => $blueprint->boolean($colName),
                            str_contains($typeName, 'json') => $blueprint->text($colName),
                            str_contains($typeName, 'date') || str_contains($typeName, 'time') => $blueprint->text($colName),
                            str_contains($typeName, 'float') || str_contains($typeName, 'double') || str_contains($typeName, 'numeric') || str_contains($typeName, 'real') => $blueprint->float($colName),
                            default => $blueprint->text($colName),
                        };

                        if ($isNullable && ! ($column['auto_increment'] ?? false)) {
                            $colDef->nullable();
                        }
                    }
                });

                // Fetch rows in chunks and insert into SQLite
                $totalRows = 0;
                $orderColumn = ! empty($columns) ? $columns[0]['name'] : null;

                $query = DB::connection($fromConnection)->table($sourceTable);
                if ($orderColumn !== null) {
                    $query->orderBy($orderColumn);
                }

                $query->chunk(500, function ($rows) use ($sqliteTable, &$totalRows): void {
                    $insertData = [];

                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $cleanedRow = [];

                        foreach ($rowArray as $key => $value) {
                            if (is_bool($value)) {
                                $cleanedRow[$key] = $value ? 1 : 0;
                            } elseif (is_array($value) || is_object($value)) {
                                $cleanedRow[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                            } else {
                                $cleanedRow[$key] = $value;
                            }
                        }

                        $insertData[] = $cleanedRow;
                    }

                    if ($insertData !== []) {
                        DB::connection('sqlite_backup')->table($sqliteTable)->insert($insertData);
                        $totalRows += count($insertData);
                    }
                });

                $summary[] = [
                    'table' => $sqliteTable,
                    'rows' => $totalRows,
                    'status' => 'OK',
                ];
            } catch (Throwable $e) {
                $this->error("Error backing up table [{$sourceTable}]: {$e->getMessage()}");

                $summary[] = [
                    'table' => $sqliteTable,
                    'rows' => 0,
                    'status' => 'FAILED: '.$e->getMessage(),
                ];
            }
        }

        DB::connection('sqlite_backup')->statement('PRAGMA foreign_keys = ON;');

        $this->newLine();
        $this->table(['Table', 'Copied Rows', 'Status'], $summary);

        $this->info("Backup finished successfully! File saved at: [{$targetPath}]");

        return self::SUCCESS;
    }
}
