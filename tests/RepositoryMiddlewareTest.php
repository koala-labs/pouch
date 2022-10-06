<?php

namespace Koala\Pouch\Tests;

use Koala\Pouch\Contracts\PouchResource;
use Koala\Pouch\EloquentRepository;
use Koala\Pouch\Facades\ModelResolver;
use Koala\Pouch\Middleware\RepositoryMiddleware;
use Koala\Pouch\Providers\RepositoryServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Koala\Pouch\Tests\Models\User;
use Mockery;

class RepositoryMiddlewareTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [RepositoryServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app); // TODO: Change the autogenerated stub

        $app['config']->set('pouch', [
            'eager_load_depth' => 1,
        ]);
    }

    public function testItBuildsARepositoryFromARequestIfNoIdIsSetOnGet()
    {
        $request = Mockery::mock(Request::class);
        $route   = Mockery::mock(Route::class);

        $request->shouldReceive('route')->once()->andReturn($route);

        ModelResolver::shouldReceive('resolveModelClass')->once()->andReturn(RepositoryMiddlewareTestStubModel::class);

        $route->shouldReceive('parametersWithoutNulls')->once()->andReturn([]);

        $request->shouldReceive('method')->once()->andReturn('GET');

        $request->shouldReceive('get')->with('filters')->once()->andReturn([]);
        $request->shouldReceive('get')->with('sort')->once()->andReturn([]);
        $request->shouldReceive('get')->with('group')->once()->andReturn([]);
        $request->shouldReceive('get')->with('include')->once()->andReturn([]);
        $request->shouldReceive('get')->with('aggregate')->once()->andReturn([]);
        $request->shouldReceive('get')->with('pick')->once()->andReturn([]);

        $middleware = new RepositoryMiddleware();
        $repository = $middleware->buildRepository($request);

        $this->assertTrue($repository instanceof EloquentRepository);
        $this->assertSame($repository->getModelClass(), RepositoryMiddlewareTestStubModel::class);
    }

    public function testItBuildsARepositoryFromARequestIfIdIsSetOnGet()
    {
        $request = Mockery::mock(Request::class);
        $route   = Mockery::mock(Route::class);

        $request->shouldReceive('route')->once()->andReturn($route);

        ModelResolver::shouldReceive('resolveModelClass')->once()->andReturn(RepositoryMiddlewareTestStubModel::class);

        $route->shouldReceive('parametersWithoutNulls')->once()->andReturn(['857']);

        $request->shouldReceive('method')->once()->andReturn('GET');

        $request->shouldReceive('get')->with('filters')->once()->andReturn([]);
        $request->shouldReceive('get')->with('sort')->once()->andReturn([]);
        $request->shouldReceive('get')->with('group')->once()->andReturn([]);
        $request->shouldReceive('get')->with('include')->once()->andReturn([]);
        $request->shouldReceive('get')->with('aggregate')->once()->andReturn([]);
        $request->shouldReceive('get')->with('pick')->once()->andReturn([]);

        $middleware = new RepositoryMiddleware();
        $repository = $middleware->buildRepository($request);

        $this->assertTrue($repository instanceof EloquentRepository);
        $this->assertSame($repository->getModelClass(), RepositoryMiddlewareTestStubModel::class);

        $this->assertSame('857', $repository->getInputId());
    }

    public function testItBuildsARepositoryFromARequestIfMethodIsNotGetAndSetsInput()
    {
        $request = Mockery::mock(Request::class);
        $route   = Mockery::mock(Route::class);

        $request->shouldReceive('route')->once()->andReturn($route);

        ModelResolver::shouldReceive('resolveModelClass')->once()->andReturn(RepositoryMiddlewareTestStubModel::class);

        $route->shouldReceive('parametersWithoutNulls')->once()->andReturn(['857']);

        $request->shouldReceive('method')->once()->andReturn('PUT');

        $request->shouldReceive('all')->once()->andReturn([
            'foo' => 'bar',
            'baz' => 'bat',
        ]);
        $request->shouldReceive('get')->with('filters')->once()->andReturn([]);
        $request->shouldReceive('get')->with('sort')->once()->andReturn([]);
        $request->shouldReceive('get')->with('group')->once()->andReturn([]);
        $request->shouldReceive('get')->with('include')->once()->andReturn([]);
        $request->shouldReceive('get')->with('aggregate')->once()->andReturn([]);
        $request->shouldReceive('get')->with('pick')->once()->andReturn([]);

        $middleware = new RepositoryMiddleware();
        $repository = $middleware->buildRepository($request);

        $this->assertTrue($repository instanceof EloquentRepository);
        $this->assertSame($repository->getModelClass(), RepositoryMiddlewareTestStubModel::class);

        $this->assertSame([
            'id'  => '857',
            'foo' => 'bar',
            'baz' => 'bat',
        ], $repository->getInput());
    }

    public function testItBuildsARepositoryFromARequestAndSetsModificationsOnQuery()
    {
        $request = Mockery::mock(Request::class);
        $route   = Mockery::mock(Route::class);

        $request->shouldReceive('route')->once()->andReturn($route);

        ModelResolver::shouldReceive('resolveModelClass')->once()->andReturn(RepositoryMiddlewareTestStubModel::class);

        $route->shouldReceive('parametersWithoutNulls')->once()->andReturn(['857']);

        $request->shouldReceive('method')->once()->andReturn('GET');

        $request->shouldReceive('get')->with('filters')->once()->andReturn([
            'foo' => 'filters',
        ]);
        $request->shouldReceive('get')->with('sort')->once()->andReturn([
            'foo' => 'sort',
        ]);
        $request->shouldReceive('get')->with('group')->once()->andReturn([
            'foo' => 'group',
        ]);
        $request->shouldReceive('get')->with('include')->once()->andReturn([
            'foo' => 'include',
        ]);
        $request->shouldReceive('get')->with('aggregate')->once()->andReturn([
            'foo' => 'aggregate',
        ]);
        $request->shouldReceive('get')->with('pick')->once()->andReturn([]);

        $middleware = new RepositoryMiddleware();
        $repository = $middleware->buildRepository($request);

        $this->assertTrue($repository instanceof EloquentRepository);
        $this->assertSame($repository->getModelClass(), RepositoryMiddlewareTestStubModel::class);

        $this->assertSame('857', $repository->getInputId());

        $this->assertSame(['foo' => 'filters'], $repository->modify()->getFilters());
        $this->assertSame(['foo' => 'sort'], $repository->modify()->getSortOrder());
        $this->assertSame(['foo' => 'group'], $repository->modify()->getGroupBy());
        $this->assertSame(['foo' => 'include'], $repository->modify()->getEagerLoads());
        $this->assertSame(['foo' => 'aggregate'], $repository->modify()->getAggregate());
    }

    public function testItBuildsARepositoryFromARequestAndSetsPicks()
    {
        $request = Mockery::mock(Request::class);
        $route   = Mockery::mock(Route::class);

        $request->shouldReceive('route')->once()->andReturn($route);

        ModelResolver::shouldReceive('resolveModelClass')->once()->andReturn(RepositoryMiddlewareTestStubModel::class);

        $route->shouldReceive('parametersWithoutNulls')->once()->andReturn(['857']);

        $request->shouldReceive('method')->once()->andReturn('GET');

        $request->shouldReceive('get')->with('filters')->once()->andReturn([
            'foo' => 'filters',
        ]);
        $request->shouldReceive('get')->with('sort')->once()->andReturn([
            'foo' => 'sort',
        ]);
        $request->shouldReceive('get')->with('group')->once()->andReturn([
            'foo' => 'group',
        ]);
        $request->shouldReceive('get')->with('include')->once()->andReturn([
            'foo' => 'include',
        ]);
        $request->shouldReceive('get')->with('aggregate')->once()->andReturn([
            'foo' => 'aggregate',
        ]);
        $request->shouldReceive('get')->with('pick')->once()->andReturn([
           'foo', 'bar', 'baz', 'yar', 'derp'
        ]);

        $middleware = new RepositoryMiddleware();
        $repository = $middleware->buildRepository($request);

        $this->assertTrue($repository instanceof EloquentRepository);
        $this->assertSame($repository->getModelClass(), RepositoryMiddlewareTestStubModel::class);

        $this->assertSame('857', $repository->getInputId());

        $this->assertSame(['foo' => 'filters'], $repository->modify()->getFilters());
        $this->assertSame(['foo' => 'sort'], $repository->modify()->getSortOrder());
        $this->assertSame(['foo' => 'group'], $repository->modify()->getGroupBy());
        $this->assertSame(['foo' => 'include'], $repository->modify()->getEagerLoads());
        $this->assertSame(['foo' => 'aggregate'], $repository->modify()->getAggregate());
        $this->assertSame(['foo', 'bar', 'baz', 'yar', 'derp'], $repository->modify()->getPicks());
    }

    /**
     * @dataProvider pickAndExpectedResultProvider
     */
    public function testItCanAcceptPicksFromRequest($input, $expectedPicks)
    {
        $middleware = new RepositoryMiddleware();
        $request    = Request::create('/users', 'GET', ['pick' => $input]);
        $route      = new Route('GET', $request->getUri(), ['uses' => fn () => null, 'resource' => User::class]);

        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $repository = $middleware->buildRepository($request);

        $this->assertEquals($expectedPicks, $repository->modify()->getPicks());
    }

    public function pickAndExpectedResultProvider()
    {
        return [
            //?pick=a,b,c
            ['a,b,c', ['a', 'b', 'c']],
            ['a ,b, c', ['a', 'b', 'c']],
            ['a ,b,', ['a', 'b']],
            ['a', ['a']],
            ['0,1,2', ['0', '1', '2']],
            ['0', ['0']],
            [null, []],
            [['a', 'b'], ['a', 'b']],
            ['', []],
            //?pick[]=a&pick[]=b&pick[]=c
            [['a', 'b', 'c'], ['a', 'b', 'c']],
            [['0','1','2'], ['0', '1', '2']],
            [[0, 1, 2], ['0', '1', '2']],
        ];
    }
}

class RepositoryMiddlewareTestStubModel extends Model implements PouchResource
{
    /**
     * Get the list of fields fillable by the repository
     *
     * @return array
     */
    public function getRepositoryFillable(): array
    {
        return [];
    }

    /**
     * Get the list of relationships fillable by the repository
     *
     * @return array
     */
    public function getRepositoryIncludable(): array
    {
        return [];
    }

    /**
     * Get the list of fields filterable by the repository
     *
     * @return array
     */
    public function getRepositoryFilterable(): array
    {
        return [];
    }
}
