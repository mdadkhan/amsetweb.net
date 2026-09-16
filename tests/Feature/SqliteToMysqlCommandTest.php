<?php

namespace Tests\Feature;

use Tests\TestCase;

class SqliteToMysqlCommandTest extends TestCase
{
    public function test_transfer_requires_target_database_configuration(): void
    {
        config()->set('database.connections.mysql_import.database', null);
        config()->set('database.connections.mysql_import.username', null);

        $this->artisan('amset:sqlite-to-mysql', ['--schema-only' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Set MYSQL_IMPORT_DATABASE and MYSQL_IMPORT_USERNAME in .env first.')
            ->assertFailed();
    }

    public function test_transfer_rejects_an_invalid_chunk_size(): void
    {
        $this->artisan('amset:sqlite-to-mysql', ['--chunk' => 0, '--no-interaction' => true])
            ->expectsOutputToContain('--chunk must be an integer between 1 and 10000.')
            ->assertFailed();
    }
}
