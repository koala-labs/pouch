<?php

namespace Koala\Pouch\Utility;

use Koala\Pouch\Contracts\PouchResource;
use Koala\Pouch\Contracts\ModelResolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Console\AppNamespaceDetectorTrait;

/**
 * Class RouteGuessingModelResolver
 *
 * A ModelResolver which guesses the resource currently being worked on based on the route name.
 *
 * @package Koala\Pouch\Utility
 */
class RouteGuessingModelResolver implements ModelResolver
{
    use AppNamespaceDetectorTrait;

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
            list(, $alias) = Arr::reverse(explode('.', $route->getName()));

            $model_class = $this->namespaceModel(Str::studly(Str::singular($alias)));

            if (is_a($model_class, PouchResource::class, true)) {
                return $model_class;
            }

            throw new \LogicException(sprintf('%s must be an instance of %s', $model_class, PouchResource::class));
        }

        throw new \LogicException('Unable to resolve model from improperly named route');
    }

    /**
     * Attach the app namespace to the model and return it.
     *
     * @param string $model_class
     * @return string
     */
    final public function namespaceModel($model_class)
    {
        return sprintf('%s%s', $this->getAppNamespace(), $model_class);
    }
}
