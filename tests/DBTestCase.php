<?php

namespace Fuzz\MagicBox\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class DBTestCase extends TestCase
{
    use DatabaseTransactions;

	protected $artisan;

	public function setUp(): void
	{
		parent::setUp();

		$this->artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
		$this->artisan->call(
			'migrate', [
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
			'database.connections.testbench', [
				'driver'   => 'sqlite',
				'database' => ':memory:',
				'prefix'   => ''
			]
		);
	}
}
