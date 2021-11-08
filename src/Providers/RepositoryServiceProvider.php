<?php

namespace Koala\Pouch\Providers;

use Koala\Pouch\Contracts\Repository;
use Koala\Pouch\EloquentRepository;
use Koala\Pouch\Facades\ModelResolver;
use Koala\Pouch\Utility\ExplicitModelResolver;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
	/**
	 * Register any other events for your application.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([$this->configPath() => config_path('pouch.php')], 'config');
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		app()->singleton(Repository::class, function() {
			return new EloquentRepository;
		});

		app()->bind(ModelResolver::class, function() {
			return new ExplicitModelResolver;
		});
	}

	/**
	 * Get the config path
	 *
	 * @return string
	 */
	protected function configPath()
	{
		return realpath(__DIR__ . '/../../config/magic-box.php');
	}
}
