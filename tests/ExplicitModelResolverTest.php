<?php

namespace Koala\Pouch\Tests;

use Koala\Pouch\Exception\ModelNotResolvedException;
use Koala\Pouch\Tests\Models\Post;
use Koala\Pouch\Tests\Models\User;
use Koala\Pouch\Utility\ExplicitModelResolver;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route;
use Mockery;

class ExplicitModelResolverTest extends TestCase
{
    /**
     * Given a route has a property named resource
     * When the model is resolved
     * Then the resolver should return that properties value.
     *
     * Example:
     *      $router->get('/users', ['resource' => User::class, 'uses' => 'UserController@getUsers']);
     */
    public function testCanResolveModelClassOnRoute()
    {
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getAction')->twice()->andReturn([
            'resource' => User::class,
        ]);

        $model = (new ExplicitModelResolver())->resolveModelClass($route);

        $this->assertEquals(User::class, $model);
        $this->assertNotEquals(Post::class, $model);
    }

    /**
     * Given a controller has a static property named $resource
     * When the model is resolved
     * Then the resolver should return the properties value.
     *
     * Example:
     *      $router->get('/users', ['uses' => 'UserController@getUsers']);
     *
     *      class UserController extends BaseController {
     *          ...
     *
     *          public static $resource = User::class;
     *
     *          ...
     *      }
     */
    public function testCanResolveModelClassOnController()
    {
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getAction')->zeroOrMoreTimes()->andReturn([
            'uses' => UserController::class.'@getUsers',
        ]);

        $model = (new ExplicitModelResolver())->resolveModelClass($route);

        $this->assertEquals(User::class, $model);
        $this->assertNotEquals(Post::class, $model);
    }

    public function testItThrowsModelNotResolvedExceptionIfControllerDoesNotHaveResource()
    {
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getAction')->zeroOrMoreTimes()->andReturn([
            'uses' => ExplicitExplicitModelResolverTestStubControllerNoResource::class.'@getUsers',
        ]);

        $this->expectException(ModelNotResolvedException::class);
        $model = (new ExplicitModelResolver())->resolveModelClass($route);
    }

    /**
     * Given the route is a closure
     * When the model is resolved
     * Then the resolver should return false because it cannot be resolved.
     *
     * Example:
     *      $router->get('/users', function($request) {
     *          return response()->json([], 200);
     *      })
     */
    public function testItThrowsModelNotResolvedExceptionIfModelCouldNotBeResolved()
    {
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getAction')->zeroOrMoreTimes()->andReturn([
            'uses' => function () {
                return null;
            },
        ]);

        $this->expectException(ModelNotResolvedException::class);
        $model = (new ExplicitModelResolver())->resolveModelClass($route);
    }

    public function testItCanGetRouteMethodFromRoute()
    {
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('getAction')->zeroOrMoreTimes()->andReturn([
            'uses' => 'FooController@barMethod',
        ]);

        $this->assertSame('barMethod', (new ExplicitModelResolver())->getRouteMethod($route));
    }
}


/**
 * Class UserController
 *
 * @package Fuzz\MagicBox\Tests
 */
class UserController extends Controller
{
    public static $resource = User::class;
}

class ExplicitExplicitModelResolverTestStubControllerNoResource
{
}
