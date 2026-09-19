<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class TransferSqliteToMysql extends Command
{
    protected $signature = 'amset:sqlite-to-mysql
        {--schema-only : Create or update the MySQL schema without copying data}
        {--create-database : Create the configured MySQL database when it does not exist}
        {--fresh : Drop all target MySQL tables before running migrations}
        {--force : Allow destructive or production execution without confirmation}
        {--chunk=500 : Number of source records copied per batch}';

    protected $description = 'Create the MySQL schema and optionally copy durable application data from SQLite';

    private const TABLES = [
        'users',
        'pages',
        'posts',
        'contact_submissions',
        'initiatives',
        'people',
        'timeline_events',
        'conferences',
        'galleries',
        'gallery_items',
        'membership_plans',
        'members',
        'events',
        'event_tickets',
        'event_registrations',
        'donation_campaigns',
        'donations',
        'payments',
    ];

    public function handle(): int
    {
        if (! extension_loaded('pdo_sqlite') || ! extension_loaded('pdo_mysql')) {
            $this->components->error('Both pdo_sqlite and pdo_mysql PHP extensions are required.');

            return self::FAILURE;
        }

        $chunkSize = filter_var($this->option('chunk'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 10000],
        ]);

        if ($chunkSize === false) {
            $this->components->error('--chunk must be an integer between 1 and 10000.');

            return self::FAILURE;
        }

        $database = (string) config('database.connections.mysql_import.database');
        $username = (string) config('database.connections.mysql_import.username');

        if ($database === '' || $username === '') {
            $this->components->error('Set MYSQL_IMPORT_DATABASE and MYSQL_IMPORT_USERNAME in .env first.');

            return self::FAILURE;
        }

        if (! preg_match('/^[A-Za-z0-9_$]+$/', $database)) {
            $this->components->error('MYSQL_IMPORT_DATABASE may contain only letters, numbers, underscores, and dollar signs.');

            return self::FAILURE;
        }

        $sqlitePath = (string) config('database.connections.sqlite_import.database');

        if (! is_file($sqlitePath) || ! is_readable($sqlitePath)) {
            $this->components->error("SQLite source is not readable: {$sqlitePath}");

            return self::FAILURE;
        }

        if (($this->option('fresh') || app()->isProduction()) && ! $this->option('force')) {
            $operation = $this->option('fresh') ? 'drop all target MySQL tables' : 'write to MySQL in production';

            if (! $this->confirm("This operation will {$operation}. Continue?")) {
                return self::FAILURE;
            }
        }

        try {
            if ($this->option('create-database')) {
                $this->createDatabase($database);
            }

            DB::purge('mysql_import');
            DB::connection('mysql_import')->getPdo();

            $migrationCommand = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
            $exitCode = $this->call($migrationCommand, [
                '--database' => 'mysql_import',
                '--force' => true,
            ]);

            if ($exitCode !== self::SUCCESS) {
                return self::FAILURE;
            }

            if ($this->option('schema-only')) {
                $this->components->info("MySQL schema is ready in {$database}; no data was copied.");

                return self::SUCCESS;
            }

            $this->copyApplicationData((int) $chunkSize);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("SQLite application data was copied to MySQL database {$database}.");

        return self::SUCCESS;
    }

    private function createDatabase(string $database): void
    {
        $host = (string) config('database.connections.mysql_import.host');
        $port = (string) config('database.connections.mysql_import.port');
        $username = (string) config('database.connections.mysql_import.username');
        $password = (string) config('database.connections.mysql_import.password');
        $socket = (string) config('database.connections.mysql_import.unix_socket');
        $dsn = $socket !== ''
            ? "mysql:unix_socket={$socket};charset=utf8mb4"
            : "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->components->info("MySQL database {$database} is available.");
    }

    private function copyApplicationData(int $chunkSize): void
    {
        $source = DB::connection('sqlite_import');
        $target = DB::connection('mysql_import');
        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (self::TABLES as $table) {
                if (! $source->getSchemaBuilder()->hasTable($table)) {
                    $this->components->warn("Skipping missing SQLite table: {$table}");

                    continue;
                }

                $this->copyTable($source, $target, $table, $chunkSize);
            }
        } finally {
            $target->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function copyTable(
        ConnectionInterface $source,
        ConnectionInterface $target,
        string $table,
        int $chunkSize,
    ): void {
        $copied = 0;

        $source->table($table)->orderBy('id')->chunkById($chunkSize, function ($records) use ($target, $table, &$copied): void {
            $rows = $records->map(fn (object $record): array => (array) $record)->all();

            if ($rows === []) {
                return;
            }

            $columns = array_keys($rows[0]);
            $target->table($table)->upsert($rows, ['id'], array_values(array_diff($columns, ['id'])));
            $copied += count($rows);
        });

        $this->line("  {$table}: {$copied} rows");
    }
}
