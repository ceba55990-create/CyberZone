<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);
        DB::purge('sqlite');

        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new LogicException('Feature tests require an isolated SQLite in-memory database.');
        }

        foreach ([
            '2026_11_09_010803_create_country_table.php',
            '2026_11_09_042646_create_computers_table.php',
            '2026_11_22_000000_create_tracker_table.php',
            '2026_11_22_000001_add_status_filter_to_tracker_table.php',
        ] as $migrationFile) {
            $migration = require database_path('migrations/'.$migrationFile);
            $migration->up();
        }
    }
}
