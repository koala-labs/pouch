<?php

namespace Koala\Pouch\Utility;

use Koala\Pouch\Contracts\PouchResource;
use Koala\Pouch\Contracts\ModelResolver;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Koala\Pouch\Exception\ModelNotResolvedException;

/**
 * Class RouteGuessingModelResolver
 *
 * A ModelResolver which guesses the resource currently being worked on based on the route name.
 *
 * @package Koala\Pouch\Utility
 */
class RouteGuessingModelResolver implements ModelResolver
{
    /**
     * Resolve and return the model class for requests.
     *
     * @param \Illuminate\Routing\Route $route
     * @return string
     */
    public function resolveModelClass(Route $route): string
    {
        // The plural resource name is always the second URL segment, after the API version
        $route_name = $route->getName();

        if (! is_null($route_name) && strpos($route_name, '.') !== false) {
            $alias = (explode('.', $route_name) ?: [])[1] ?? "";

            $model_class = $this->namespaceModel(Str::studly(Str::singular($alias)));

            if (is_a($model_class, PouchResource::class, true)) {
                return $model_class;
            }

            throw new \LogicException(sprintf('%s must be an instance of %s', $model_class, PouchResource::class));
        }

        throw new ModelNotResolvedException('Unable to resolve model from improperly named route');
    }

    /**
     * Attach the app namespace to the model and return it.
     *
     * @param string $model_class
     * @return string
     */
    final public function namespaceModel($model_class)
    {
        return sprintf('%s%s', App::getNamespace(), $model_class);
    }
}
