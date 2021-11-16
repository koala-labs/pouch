<?php

namespace Koala\Pouch\Contracts;

use Illuminate\Routing\Route;

/**
 * Interface ModelResolver
 *
 * A ModelResolver determines which PouchResource is being worked on via a Route.
 *
 * @package Koala\Pouch\Contracts
 */
interface ModelResolver
{
    /**
     * Resolve and return the model class for requests.
     *
     * @param \Illuminate\Routing\Route $route
     * @return string
     */
    public function resolveModelClass(Route $route): string;
}
