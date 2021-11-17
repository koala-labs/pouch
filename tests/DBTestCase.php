<?php

namespace Koala\Pouch\Tests;

use Illuminate\Support\Facades\Artisan;
use League\Flysystem\Config;

abstract class DBTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call(
            'migrate',
            [
                '--database' => 'testbench',
                '--path'     => '../../../../tests/migrations',
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
