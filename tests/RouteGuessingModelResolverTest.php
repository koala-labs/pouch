<?php

namespace Koala\Pouch\Tests;

use Illuminate\Support\Facades\App;
use Koala\Pouch\Contracts\PouchResource;
use Koala\Pouch\Exception\ModelNotResolvedException;
use Koala\Pouch\Tests\Models\Post;
use Koala\Pouch\Tests\Models\Tag;
use Koala\Pouch\Tests\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route;
use Koala\Pouch\Utility\RouteGuessingModelResolver;
use Mockery;

class RouteGuessingModelResolverTest extends TestCase
{
    /**
     * @dataProvider routeNameAndModelClassProvider
     */
    public function testCanResolveModelClassFromRouteName(string $routeName, string $modelClass)
    {
        $route = Mockery::mock(Route::class)
            ->shouldReceive('getName')->atLeast()->once()->andReturn($routeName)
            ->getMock();
        App::shouldReceive('getNamespace')->once()->andReturn(__NAMESPACE__.'\\Models\\');

        $model = (new RouteGuessingModelResolver())->resolveModelClass($route);

        $this->assertEquals($modelClass, $model);
    }

    public function routeNameAndModelClassProvider(): array
    {
        return [
          ['users.v1', User::class],
          ['users.posts.v1', Post::class],
          ['users.posts.tags', Post::class],
          ['users.posts.tags.v1', Tag::class],
        ];
    }

    public function testItThrowsAnExceptionIfTheModelCannotBeResolvedFromTheRouteName()
    {
        $route = Mockery::mock(Route::class)
            ->shouldReceive('getName')->atLeast()->once()->andReturn('foobar')
            ->getMock();

        $this->expectExceptionObject(new ModelNotResolvedException('Unable to resolve model from improperly named route'));
        (new RouteGuessingModelResolver())->resolveModelClass($route);
    }

    public function testItThrowsAnExceptionIfTheResolvedModelDoesNotExtendPouchResource()
    {
        $route = Mockery::mock(Route::class)
            ->shouldReceive('getName')->once()->andReturn('not_a_pouch_resource.v1')
            ->getMock();
        App::shouldReceive('getNamespace')->once()->andReturn(__NAMESPACE__.'\\Models\\');

        $this->expectExceptionObject(new \LogicException(__NAMESPACE__.'\\Models\\NotAPouchResource must be an instance of ' . PouchResource::class));
        (new RouteGuessingModelResolver())->resolveModelClass($route);
    }
}


/**
 * Class UserController
 *
 * @package Koala\Pouch\Tests
 */
class UserController extends Controller
{
    public static $resource = User::class;
}

class RouteGuessingModelResolverTestStubControllerNoResource
{
}
