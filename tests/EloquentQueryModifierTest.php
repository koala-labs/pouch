<?php

namespace Koala\Pouch\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Koala\Pouch\EloquentAccessControl;
use Koala\Pouch\EloquentQueryModifier;
use Koala\Pouch\Tests\Models\User;
use Koala\Pouch\Tests\Seeds\FilterDataSeeder;

class EloquentQueryModifierTest extends DBTestCase
{
    public function testItAssignmentOfPicksIsReflected()
    {
        $modifier = new EloquentQueryModifier();

        $modifier->setPicks(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $modifier->getPicks());

        $modifier->setPicks([]);
        $this->assertEquals([], $modifier->getPicks());

        $modifier->addPick('foo');
        $this->assertEquals(['foo'], $modifier->getPicks());

        $modifier->addPick('');
        $this->assertEquals(['foo'], $modifier->getPicks());

        $modifier->addPick('bar');
        $this->assertEquals(['foo', 'bar'], $modifier->getPicks());

        $modifier->addPicks(['foo', 'bar', 'bar', 'bar', 'foo', 'baz']);
        $this->assertEquals(['foo', 'bar', 'baz'], $modifier->getPicks());

        $modifier->addPicks(['foo', 'bar', 'baz', 'bar', 'foo', 'baz']);
        $this->assertEquals(['foo', 'bar', 'baz'], $modifier->getPicks());
    }

    public function testItDoesNotApplySelectWhenThereAreNoPicksDefined()
    {
        $model = new User();
        $query = $model->query();

        (new EloquentQueryModifier())->setQuery($query)->apply(new EloquentAccessControl(), get_class($model));

        $this->assertNull($query->getQuery()->columns);
    }

    public function testItDoesNotSelectPicksThatAreNotInTheModelTable()
    {
        $model = new User();
        $query = $model->query();

        (new EloquentQueryModifier())->setQuery($query)
            ->setPicks([$model->getKeyName(), 'not-in-this-model'])
            ->apply(new EloquentAccessControl(), get_class($model));

        $this->assertContains($model->getKeyName(), $query->getQuery()->columns);
        $this->assertNotContains('not-in-this-model', $query->getQuery()->columns);
    }

    public function testItDoesNotApplySelectWhenNoneOfThePicksMatchColumns()
    {
        $model = new User();
        $query = $model->query();

        (new EloquentQueryModifier())->setQuery($query)
            ->setPicks(['not-in-this-model', 'another-not-in-this-model'])
            ->apply(new EloquentAccessControl(), get_class($model));

        $this->assertNull($query->getQuery()->columns);
    }

    /**
     * @dataProvider aggregateFunctionDataProvider
     */
    public function testItCanApplyAnAggregation(string $function)
    {
        $model = new User();
        $query = $model->query();

        (new EloquentQueryModifier())->setQuery($query)
            ->setAggregate([$function => 'hands'])
            ->apply(new EloquentAccessControl(), get_class($model));

        $this->assertEqualsCanonicalizing(['function' => $function, 'columns' => ['hands']], $query->getQuery()->aggregate);
    }

    public function testItOnlyAppliesTheFirstAggregation()
    {
        $model = new User();
        $query = $model->query();

        (new EloquentQueryModifier())->setQuery($query)
            ->setAggregate(['sum' => 'hands', 'avg' => 'hands', 'max' => 'hands'])
            ->apply(new EloquentAccessControl(), get_class($model));

        $this->assertEqualsCanonicalizing(['function' => 'sum', 'columns' => ['hands']], $query->getQuery()->aggregate);
    }

    public function testItDoesNotApplyInvalidAggregationFunctions()
    {
        $model = new User();
        $query = $model->query();

        (new EloquentQueryModifier())->setQuery($query)
            ->setAggregate(['not-valid' => 'hands'])
            ->apply(new EloquentAccessControl(), get_class($model));

        $this->assertNull($query->getQuery()->aggregate);
    }

    protected function aggregateFunctionDataProvider()
    {
        return [
            'Count' => ['count'],
            'Min' => ['min'],
            'Max' => ['max'],
            'Sum' => ['sum'],
            'Avg' => ['avg']
        ];
    }
}
