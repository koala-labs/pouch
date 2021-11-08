<?php

namespace Fuzz\MagicBox\Tests;

use Fuzz\MagicBox\Filter;
use Fuzz\MagicBox\Tests\Models\User;
use Fuzz\MagicBox\Tests\Seeds\FilterDataSeeder;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FilterTest extends DBTestCase
{
    /**
	 * Set up and seed the database with seed data
	 *
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();

		$this->artisan->call(
			'db:seed', [
				'--class' => FilterDataSeeder::class
			]
		);
	}

	/**
	 * Retrieve a query for the model
	 *
	 * @param string $model_class
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function getQuery($model_class)
	{
		return $model_class::query();
	}

	private function getModelColumns($model_class)
	{
		$temp_instance = new $model_class;

		return Schema::getColumnListing($temp_instance->getTable());
	}

	public function testItModifiesQuery()
	{
		$filters        = ['name' => '^lskywalker'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$original_query = clone $query;
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$this->assertNotSame($original_query, $query);
	}

	public function testItStartsWith()
	{
		$filters        = ['username' => '^lskywalker'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		$this->assertEquals('lskywalker@galaxyfarfaraway.com', $results->first()->username);
	}

	public function testItFiltersOrStartsWith()
	{
		$filters        = [
			'username' => '^lskywalker',
			'or'       => ['username' => '^solocup']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));

		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com']));
		}
	}

	public function testItEndsWith()
	{
		$filters        = ['name' => '$gana'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		$this->assertEquals('lorgana@galaxyfarfaraway.com', $results->first()->username);
	}

	public function testItFiltersOrEndsWith()
	{
		$filters        = [
			'name' => '$gana',
			'or'       => ['name' => '$olo']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com']));
		}
	}

	public function testItContains()
	{
		$filters        = ['username' => '~clava'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		$this->assertEquals('chewbaclava@galaxyfarfaraway.com', $results->first()->username);
	}

	public function testItFiltersOrContains()
	{
		$filters        = [
			'username' => '~skywalker',
			'or'       => ['username' => '~clava']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItIsLessThan()
	{
        $expectedUsernames = ['chewbaclava@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com'];
		$filters        = ['times_captured' => '<1'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItFiltersOrIsLessThan()
	{
        $expectedUsernames = ['solocup@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com'];
		$filters        = [
			'times_captured' => '<1',
			'or'       => ['times_captured' => '<3']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItIsGreaterThan()
	{
		$filters        = ['hands' => '>1'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lorgana@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrIsGreaterThan()
	{
		$filters        = [
			'hands' => '>1',
			'or'       => ['hands' => '>0']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(4, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com']));
		}
	}

	public function testItIsLessThanOrEquals()
	{
		$filters        = ['hands' => '<=1'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $expectedUsernames = ['chewbaclava@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com'];
        $this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItFiltersOrIsLessThanOrEquals()
	{
        $expectedUsernames = ['chewbaclava@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com'];

		$filters        = [
			'times_captured' => '<=2',
			'or'       => ['times_captured' => '<=5']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $expectedUsernames = ['chewbaclava@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com'];
        $this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItIsGreaterThanOrEquals()
	{
		$filters        = [
			'times_captured' => '>=5',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $expectedUsernames = ['lorgana@galaxyfarfaraway.com'];
        $this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItFiltersOrIsGreaterThanOrEquals()
	{
		$filters        = [
			'times_captured' => '>=5',
			'or'       => ['times_captured' => '>=3']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $expectedUsernames = ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com'];
        $this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItEqualsString()
	{
		$filters        = [
			'username' => '=lskywalker@galaxyfarfaraway.com',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItEqualsInt()
	{
		$filters        = [
			'times_captured' => '=6',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrEqualsString()
	{

		$filters        = [
			'username' => '=lskywalker@galaxyfarfaraway.com',
			'or'       => ['username' => '=lorgana@galaxyfarfaraway.com']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrEqualsInt()
	{

		$filters        = [
			'times_captured' => '=4',
			'or'       => ['times_captured' => '=6']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItNotEqualsString()
	{

		$filters        = ['username' => '!=lorgana@galaxyfarfaraway.com'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(User::count() - 1, count($results));
		foreach ($results as $result){
			$this->assertTrue($result->username !== 'lorgana@galaxyfarfaraway.com');
		}
	}

	public function testItNotEqualsInt()
	{

		$filters        = ['times_captured' => '!=4'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(User::count() - 1, count($results));
		foreach ($results as $result){
			$this->assertTrue($result->username !== 'lskywalker@galaxyfarfaraway.com');
		}
	}

	public function testItFiltersOrNotEqualString()
	{
		$filters        = [
			'username' => '=lorgana@galaxyfarfaraway.com',
			'or'       => ['username' => '!=lskywalker@galaxyfarfaraway.com']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(User::count() - 1, count($results));
		foreach ($results as $result){
			// The only one we shouldn't get is lskywalker@galaxyfarfaraway.com'
			$this->assertNotEquals('lskywalker@galaxyfarfaraway.com', $result->username);
		}
	}

	public function testItFiltersOrNotEqualInt()
	{
		$filters        = [
			'times_captured' => '=6',
			'or'       => ['times_captured' => '!=4']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertCount(User::count() - 1, $results);
		foreach ($results as $result){
			// The only one we shouldn't get is lskywalker@galaxyfarfaraway.com'
            $this->assertNotEquals('lskywalker@galaxyfarfaraway.com', $result->username);
		}
	}

	public function testItNotNull()
	{
		$filters        = ['occupation' => 'NOT_NULL'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $expectedUsernames = ['chewbaclava@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com'];
        $this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItFiltersOrNotNull()
	{
		$filters        = [
			'username' => '~lskywalker',
			'or'       => ['occupation' => 'NOT_NULL']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $expectedUsernames = ['chewbaclava@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com'];
        $this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItNull()
	{

		$filters        = [
			'occupation' => 'NULL',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com',]));
		}
	}

	public function testItFiltersOrNull()
	{
		$filters        = [
			'occupation' => '=Jedi',
			'or' => [
				'occupation' => 'NULL',
			],
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItInString()
	{

		$filters        = ['name' => '[Chewbacca,Leia Organa,Luke Skywalker]'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItInInt()
	{

		$filters        = ['times_captured' => '[0,4,6]'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $expectedUsernames = ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com'];
        $this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItFiltersOrInString()
	{

		$filters        = [
			'name' => '[Chewbacca,Luke Skywalker]',
			'or'       => ['name' => '[Luke Skywalker,Leia Organa]']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersOrInInt()
	{
		$filters        = [
			'times_captured' => '[0,4]',
			'or'       => ['times_captured' => '[4,6]']
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $expectedUsernames = ['lorgana@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'chewbaclava@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com'];
        $this->assertCount(count($expectedUsernames), $results);

        foreach ($expectedUsernames as $expectedUsername) {
            $this->assertTrue($results->contains('username', $expectedUsername));
        }
	}

	public function testItNotInString()
	{
		$filters        = [
			'name' => '![Leia Organa,Chewbacca]',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(User::count() - 2, count($results));
		foreach ($results as $result){
			$this->assertNotEquals('leiaorgana@galaxyfarfaraway.com', $result->username);
			$this->assertNotEquals('chewbaclava@galaxyfarfaraway.com', $result->username);
		}
	}

	public function testItNotInInt()
	{

		$filters        = [
			'times_captured' => '![6,0]',
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com',]));
		}
	}

	public function testItFiltersOrNotInString()
	{
		$filters        = [
			'name' => '![Leia Organa,Chewbacca]',
			'or' => [
				'name' => '![Leia Organa,Chewbacca,Han Solo]',
			]
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

        $expectedUserNames = ['solocup@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com', 'huttboss@galaxyfarfaraway.com'];
		$this->assertCount(count($expectedUserNames), $results);
		foreach ($expectedUserNames as $expectedUserName){
			$this->assertTrue($results->contains('username', $expectedUserName));
		}
	}

	public function testItFiltersOrNotInInt()
	{

		$filters        = [
			'times_captured' => '![6,0]',
			'or' => [
				'times_captured' => '![0,4,6]'
			]
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['solocup@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersNestedRelationships()
	{

		$filters        = ['profile.favorite_cheese' => '~Gou'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(1, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItProperlyDeterminesScalarFilters()
	{
		$filters        = ['name' => '=Leia Organa,Luke Skywalker'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertCount(User::count(), $results); // It does not filter anything because this is a scalar filter
	}

	public function testItFiltersFalse()
	{
		$filters        = ['profile.is_human' => 'false'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertCount(2, $results);
        $this->assertFalse($results->pluck('profile.is_human')->contains(true));
	}

	public function testItFiltersNestedTrue()
	{
		$filters        = ['profile.is_human' => 'true'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(3, count($results));
        $this->assertFalse($results->pluck('profile.is_human')->contains(false));
	}

	public function testItFiltersNestedNull()
	{
		$filters        = ['profile.favorite_fruit' => 'NULL'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['chewbaclava@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com']));
		}
	}

	/**
	 * Check to see if filtering by id works with a many to many relationship.
	 */
	public function testItFiltersNestedBelongsToManyRelationships()
	{

		$filters        = ['posts.tags.label' => '=#mysonistheworst'];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertEquals(2, count($results));
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com']));
		}
	}

	public function testItFiltersNestedConjuctions()
	{
		$filters        = [
			'username' => '^lskywalker',
			'or'       => [
				'name' => '$gana',
				'and'      => [
					'profile.favorite_cheese' => '=Provolone',
					'username' => '$gana@galaxyfarfaraway.com'
				],
				'or' => [
					'username' => '=solocup@galaxyfarfaraway.com'
				]
			]
		];

		$model          = User::class;
		$query          = $this->getQuery($model);
		$columns        = $this->getModelColumns($model);

		Filter::applyQueryFilters($query, $filters, $columns, (new $model)->getTable());

		$results = $query->get();

		$this->assertCount(3, $results);
		foreach ($results as $result){
			$this->assertTrue(in_array($result->username, ['lorgana@galaxyfarfaraway.com', 'solocup@galaxyfarfaraway.com', 'lskywalker@galaxyfarfaraway.com']));
		}
	}

	public function testItCanIntersectAllowedFilters()
	{
		$filters = [
			'username' => '^lskywalker',
			'or'       => [
				'name' => '$gana',
				'and'      => [
					'profile.favorite_cheese' => '=Provolone',
					'username' => '$gana@galaxyfarfaraway.com'
				],
				'or' => [
					'username' => '=solocup@galaxyfarfaraway.com',
					'and' => [
						'profile.least_favorite_cheese' => '~gouda'
					]
				]
			]
		];

		$allowed = [
			'username' => true,
			'profile.favorite_cheese' => true,
		];

		$this->assertSame([
			'username' => '^lskywalker',
			'or'       => [
				'and'      => [
					'profile.favorite_cheese' => '=Provolone',
					'username' => '$gana@galaxyfarfaraway.com'
				],
				'or' => [
					'username' => '=solocup@galaxyfarfaraway.com',
				]
			]
		], Filter::intersectAllowedFilters($filters, $allowed));

		$filters = [
			'username' => '^lskywalker',
			'or'       => [
				'name' => '$gana',
				'and'      => [
					'profile.favorite_cheese' => '=Provolone',
					'username' => '$gana@galaxyfarfaraway.com'
				],
				'or' => [
					'username' => '=solocup@galaxyfarfaraway.com',
					'and' => [
						'profile.least_favorite_cheese' => '~gouda'
					]
				]
			]
		];

		$allowed = [
			// None
		];

		$this->assertSame([], Filter::intersectAllowedFilters($filters, $allowed));

		$filters = [
			'username' => '^lskywalker',
			'or'       => [
				'name' => '$gana',
				'and'      => [
					'profile.favorite_cheese' => '=Provolone',
					'username' => '$gana@galaxyfarfaraway.com'
				],
				'or' => [
					'username' => '=solocup@galaxyfarfaraway.com',
					'and' => [
						'profile.least_favorite_cheese' => '~gouda'
					]
				]
			]
		];

		$allowed = [
			'username' => true,
			'profile.favorite_cheese' => true,
			'profile.least_favorite_cheese' => true,
			'name' => true,
		];

		$this->assertSame([
			'username' => '^lskywalker',
			'or'       => [
				'name' => '$gana',
				'and'      => [
					'profile.favorite_cheese' => '=Provolone',
					'username' => '$gana@galaxyfarfaraway.com'
				],
				'or' => [
					'username' => '=solocup@galaxyfarfaraway.com',
					'and' => [
						'profile.least_favorite_cheese' => '~gouda'
					]
				]
			]
		], Filter::intersectAllowedFilters($filters, $allowed));
	}
}
