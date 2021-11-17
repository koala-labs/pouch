<?php

namespace Koala\Pouch\Tests;

use Illuminate\Support\Facades\Artisan;

abstract class DBTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call(
            'migrate',
            [
                '--database' => 'testing',
                '--path'     => '../../../../tests/migrations',
            ]
        );
    }
}
