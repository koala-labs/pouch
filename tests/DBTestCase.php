<?php

namespace Koala\Pouch\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class DBTestCase extends TestCase
{
    use RefreshDatabase {
        RefreshDatabase::migrateFreshUsing as parentMigrateFreshUsing;
        RefreshDatabase::migrateUsing as parentMigrateUsing;
    }
    public $mockConsoleOutput = false;

    protected function migrateUsing(): array
    {
        return array_merge(
            $this->parentMigrateUsing(),
            [
                '--database' => 'testbench',
                '--path'     => __DIR__ . '/migrations',
                '--realpath' => true
            ]
        );
    }

    protected function migrateFreshUsing(): array
    {
        $migrationPath = __DIR__ . '/migrations';

        return array_merge(
            $this->parentMigrateFreshUsing(),
            [
                '--database' => 'testbench',
                '--path'     => $migrationPath,
                '--realpath' => true
            ]
        );
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'testbench');
        $app['config']->set(
            'database.connections.testbench',
            [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => ''
            ]
        );
    }
}
