<?php

namespace Koala\Pouch\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\Paginator;

/**
 * Interface Repository
 *
 * A Repository is an implementation of the core MagicBox functionality and it is responsible for pulling in all
 * relevant logic.
 *
 * @package Koala\Pouch\Contracts
 */
interface Repository
{
    /**
     * Access the AccessControl instance
     *
     * @return \Koala\Pouch\Contracts\AccessControl
     */
    public function accessControl(): AccessControl;

    /**
     * Access the QueryModifier instance
     *
     * @return \Koala\Pouch\Contracts\QueryModifier
     */
    public function modify(): QueryModifier;

    /**
     * Access the QueryFilterContainer instance
     *
     * @return \Koala\Pouch\Contracts\QueryFilterContainer
     */
    public function queryFilters(): QueryFilterContainer;

    /**
     * Set the model for an instance of this resource controller.
     *
     * @param string $model_class
     * @return \Koala\Pouch\Contracts\Repository
     */
    public function setModelClass($model_class): Repository;

    /**
     * Get the model class.
     *
     * @return string
     */
    public function getModelClass(): string;

    /**
     * Set input manually.
     *
     * @param array $input
     * @return \Koala\Pouch\Contracts\Repository
     */
    public function setInput(array $input): Repository;

    /**
     * Get input.
     *
     * @return array
     */
    public function getInput(): array;

    /**
     * Get the PK name
     *
     * @return string
     */
    public function getKeyName(): string;

    /**
     * Determine if the model exists
     *
     * @return bool
     */
    public function exists(): bool;

    /**
     * Find an instance of a model by ID.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find($id);

    /**
     * Find an instance of a model by ID, or fail.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findOrFail($id): Model;

    /**
     * Get all elements against the base query.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(): Collection;

    /**
     * Return paginated response.
     *
     * @param  int $per_page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function paginate($per_page): Paginator;

    /**
     * Count all elements against the base query.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Determine if the base query returns a nonzero count.
     *
     * @return bool
     */
    public function hasAny(): bool;

    /**
     * Get a random value.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function random(): Model;

    /**
     * Get the primary key from input.
     *
     * @return mixed
     */
    public function getInputId();

    /**
     * Create a model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(): Model;

    /**
     * Read a model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function read(): Model;

    /**
     * Update a model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update(): Model;

    /**
     * Update a model.
     *
     * @return boolean
     */
    public function delete(): bool;

    /**
     * Save a model, regardless of whether or not it is "new".
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function save(): Model;

    /**
     * Get the first element against the base query.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function first(): ?Model;

    /**
     * Get the first element against the base query, or fail if no results are found.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrFail(): Model;
}
