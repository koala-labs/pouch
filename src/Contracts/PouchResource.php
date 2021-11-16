<?php

namespace Koala\Pouch\Contracts;

/**
 * Interface PouchResource
 *
 * A PouchResource is a resource (Model) which can be accessed via MagicBox.
 *
 * @package Koala\Pouch\Contracts
 */
interface PouchResource
{
    /**
     * Get the list of fields fillable by the repository
     *
     * @return array
     */
    public function getRepositoryFillable(): array;

    /**
     * Get the list of relationships fillable by the repository
     *
     * @return array
     */
    public function getRepositoryIncludable(): array;

    /**
     * Get the list of fields filterable by the repository
     *
     * @return array
     */
    public function getRepositoryFilterable(): array;
}
