<?php

namespace Koala\Pouch;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Koala\Pouch\Contracts\AccessControl;
use Koala\Pouch\Contracts\QueryFilterContainer;
use Koala\Pouch\Contracts\QueryModifier;
use Koala\Pouch\Contracts\Repository;
use Koala\Pouch\Utility\ChecksModelFields;
use Koala\Pouch\Utility\ChecksRelations;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Class EloquentRepository
 *
 * A Repository implementation for Eloquent.
 *
 * @package Koala\Pouch
 */
class EloquentRepository implements Repository
{
    use ChecksRelations;
    use ChecksModelFields;

    /**
     * An instance variable specifying the model handled by this repository.
     *
     * @var string
     */
    private $model_class;

    /**
     * An instance variable specifying the input passed to model instances.
     *
     * @var array
     */
    private $input = [];

    /**
     * PK name
     *
     * @var string
     */
    private $key_name = 'id';

    /**
     * AccessCompiler storage
     *
     * @var \Koala\Pouch\Contracts\AccessControl
     */
    protected $access_compiler;

    /**
     * QueryModifier storage
     *
     * @var \Koala\Pouch\Contracts\QueryModifier
     */
    protected $query_modifier;

    /**
     * QueryFilterContainer storage
     *
     * @var \Koala\Pouch\Contracts\QueryFilterContainer
     */
    protected $query_filter_container;

    /**
     * Access the access compiler
     *
     * @return \Koala\Pouch\Contracts\AccessControl
     */
    public function accessControl(): AccessControl
    {
        if (is_null($this->access_compiler)) {
            $this->access_compiler = new EloquentAccessControl();
        }

        return $this->access_compiler;
    }

    /**
     * Set the AccessCompiler
     *
     * @param \Koala\Pouch\Contracts\AccessControl $access_compiler
     *
     * @return \Koala\Pouch\Contracts\Repository
     */
    public function setAccessCompiler(AccessControl $access_compiler): Repository
    {
        $this->access_compiler = $access_compiler;

        return $this;
    }

    /**
     * Access the query modifier
     *
     * @return \Koala\Pouch\Contracts\QueryModifier
     */
    public function modify(): QueryModifier
    {
        if (is_null($this->query_modifier)) {
            $this->query_modifier = new EloquentQueryModifier();
        }

        return $this->query_modifier;
    }

    /**
     * Set the QueryModifier
     *
     * @param \Koala\Pouch\Contracts\QueryModifier $query_modifier
     *
     * @return \Koala\Pouch\Contracts\Repository
     */
    public function setQueryModifier(QueryModifier $query_modifier): Repository
    {
        $this->query_modifier = $query_modifier;

        return $this;
    }

    /**
     * Access the query filters
     *
     * @return \Koala\Pouch\Contracts\QueryFilterContainer
     */
    public function queryFilters(): QueryFilterContainer
    {
        return $this->query_filter_container;
    }

    /**
     * Set the QueryFilterContainer
     *
     * @param \Koala\Pouch\Contracts\QueryFilterContainer $query_filter_container
     *
     * @return \Koala\Pouch\Contracts\Repository
     */
    public function setQueryFilters(QueryFilterContainer $query_filter_container): Repository
    {
        $this->query_filter_container = $query_filter_container;

        return $this;
    }

    /**
     * Set the model for an instance of this resource controller.
     *
     * @param string $model_class
     * @return \Koala\Pouch\Contracts\Repository
     */
    public function setModelClass($model_class): Repository
    {
        if (!is_subclass_of($model_class, Model::class)) {
            throw new InvalidArgumentException('Specified model class must be an instance of ' . Model::class);
        }

        $this->model_class = $model_class;

        /** @var \Illuminate\Database\Eloquent\Model|\Koala\Pouch\Contracts\PouchResource $instance */
        $instance = new $model_class();

        $this->accessControl()->setFillable($instance->getRepositoryFillable());
        $this->accessControl()->setIncludable($instance->getRepositoryIncludable());
        $this->accessControl()->setFilterable($instance->getRepositoryFilterable());

        $this->key_name = $instance->getKeyName();

        return $this;
    }

    /**
     * Get the PK name
     *
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->key_name;
    }

    /**
     * Determine if the model exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        return array_key_exists($this->getKeyName(), $this->getInput());
    }

    /**
     * Get the model class.
     *
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->model_class;
    }

    /**
     * Set input manually.
     *
     * @param array $input
     * @return \Koala\Pouch\Contracts\Repository
     */
    public function setInput(array $input): Repository
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Get input.
     *
     * @return array
     */
    public function getInput(): array
    {
        return $this->input;
    }

    /**
     * Base query for all behaviors within this repository.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function query()
    {
        $query = forward_static_call([
            $this->getModelClass(),
            'query',
        ]);

        $access_compiler = $this->accessControl();

        $this->modify()
            ->setQuery($query)
            ->apply($access_compiler, $this->getModelClass())
            ->applyEagerLoads($access_compiler)
            ->applyModifiers();

        return $query;
    }

    /**
     * Find an instance of a model by ID.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     */
    public function find($id)
    {
        return $this->transformModelForVisibility($this->query()->find($id));
    }

    /**
     * Find an instance of a model by ID, or fail.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findOrFail($id): Model
    {
        return $this->transformModelForVisibility($this->query()->findOrFail($id));
    }
    /**

     * Get all elements against the base query.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(): Collection
    {
        return $this->query()->get()->transform(fn ($model) => $this->transformModelForVisibility($model));
    }

    /**
     * Return paginated response.
     *
     * @param  int $per_page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function paginate($per_page): Paginator
    {
        $paginator = $this->query()->paginate($per_page);
        //Modify attribute visibility for all models in the collection
        $paginator->getCollection()->transform(fn ($model) => $this->transformModelForVisibility($model));

        return $paginator;
    }

    /**
     * Count all elements against the base query.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * Determine if the base query returns a nonzero count.
     *
     * @return bool
     */
    public function hasAny(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Get a random value.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function random(): Model
    {
        return $this->query()->orderByRaw('RAND()')->first();
    }

    /**
     * Get the primary key from input.
     *
     * @return mixed
     */
    public function getInputId()
    {
        $input = $this->getInput();

        /** @var Model $model */
        $model = $this->getModelClass();

        // If the model or the input is not set, then we cannot get an id.
        if (! $model || ! $input) {
            return null;
        }

        return Arr::get($input, (new $model())->getKeyName());
    }

    /**
     * Fill an instance of a model with all known fields.
     *
     * @param \Illuminate\Database\Eloquent\Model $instance
     * @return mixed
     * @todo support more relationship types, such as polymorphic ones!
     */
    protected function fill(Model $instance): bool
    {
        $input            = $this->getInput();
        $model_fields     = $this->getFields($instance);
        $before_relations = [];
        $after_relations  = [];
        $late_relations   = [];
        $instance_model   = get_class($instance);
        $safe_instance    = new $instance_model();

        $input = ($safe_instance->getIncrementing()) ? Arr::except($input, [$instance->getKeyName()]) :
            $input;
        $access_compiler = $this->accessControl();

        foreach ($input as $key => $value) {
            if (($relation = $this->isRelation($instance, $key, $instance_model)) && $access_compiler->isFillable($key)) {
                $relation_type = get_class($relation);

                switch ($relation_type) {
                    case BelongsTo::class:
                        $before_relations[] = [
                            'relation' => $relation,
                            'value'    => $value,
                        ];
                        break;
                    case HasOne::class:
                    case HasMany::class:
                    case BelongsToMany::class:
                        $after_relations[] = [
                            'relation' => $relation,
                            'value'    => $value,
                        ];
                        break;
                    case HasManyThrough::class:
                        //Process HasManyThrough after all other relationships since it can depend on a related
                        // intermediate object. Ex: Filling in a User that has Posts, and those Posts have a Reaction.
                        // The Post relationship has to process before the Reaction.
                        //NOTE: The model referred to by $value HAS TO ALREADY EXIST IN THE DATABASE. The input
                        // data uses nesting to infer parent model and its relationship. HasManyThrough circumvents this
                        // direct lineage.
                        //@TODO: Add the ability to refer to a nested chunk of data in input, and force the creation of the parent model first.
                        $late_relations[] = [
                            'relation' => $relation,
                            'value'    => $value
                        ];
                        break;
                }
            } elseif ((in_array($key, $model_fields) || $instance->hasSetMutator($key)) && $access_compiler->isFillable($key)) {
                $instance->{$key} = $value;
            }
        }

        unset($safe_instance);

        $this->applyRelations($before_relations, $instance);
        $instance->save();
        $this->applyRelations($after_relations, $instance);
        $this->applyRelations($late_relations, $instance);

        return true;
    }

    /**
     * Apply relations from an array to an instance model.
     *
     * @param array $specs
     * @param \Illuminate\Database\Eloquent\Model $instance
     * @return void
     */
    protected function applyRelations(array $specs, Model $instance)
    {
        foreach ($specs as $spec) {
            $this->cascadeRelation($spec['relation'], $spec['value'], $instance);
        }
    }

    /**
     * Cascade relations through saves on a model.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param array $input
     * @param \Illuminate\Database\Eloquent\Model $parent
     *
     * @return void
     */
    protected function cascadeRelation(Relation $relation, array $input, Model $parent = null)
    {
        // Make a child repository for containing the cascaded relationship through saves
        $target_model_class  = get_class($relation->getQuery()->getModel());
        $relation_repository = (new self())->setModelClass($target_model_class);

        switch (get_class($relation)) {
            case BelongsTo::class:
                /**
                 * @var \Illuminate\Database\Eloquent\Relations\BelongsTo $relation
                 */
                // For BelongsTo, simply associate by foreign key.
                // (We don't have to assume the parent model exists to do this.)
                $related = $relation_repository->setInput($input)->save();
                $relation->associate($related);
                break;
            case HasMany::class:
                /**
                 * @var \Illuminate\Database\Eloquent\Relations\HasMany $relation
                 */
                // The parent model "owns" child models; any not specified here should be deleted.
                $current_ids = $relation->pluck($this->getKeyName())->toArray();
                $new_ids     = array_filter(array_column($input, $this->getKeyName()));
                $removed_ids = array_diff($current_ids, $new_ids);
                if (!empty($removed_ids)) {
                    $relation->whereIn($this->getKeyName(), $removed_ids)->delete();
                }

                // Set foreign keys on the children from the parent, and save.
                foreach ($input as $sub_input) {
                    $sub_input[$this->getRelationsForeignKeyName($relation)] = $parent->{$this->getKeyName()};
                    $relation_repository->setInput($sub_input)->save();
                }
                break;
            case HasOne::class:
                /**
                 * @var \Illuminate\Database\Eloquent\Relations\HasOne $relation
                 */
                // The parent model "owns" the child model; if we have a new and/or different
                // existing child model, delete the old one.
                $current = $relation->getResults();
                if (!is_null($current)
                    && (!isset($input[$this->getKeyName()]) || $current->{$this->getKeyName()} !== intval($input[$this->getKeyName()]))
                ) {
                    $relation->delete();
                }

                // Set foreign key on the child from the parent, and save.
                $input[$this->getRelationsForeignKeyName($relation)] = $parent->{$this->getKeyName()};
                $relation_repository->setInput($input)->save();
                break;
            case BelongsToMany::class:
                /**
                 * @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
                 */
                // Find all the IDs to sync.
                $ids = [];

                foreach ($input as $sub_input) {
                    $id = $relation_repository->setInput($sub_input)->save()->{$this->getKeyName()};

                    // If we were passed pivot data, pass it through accordingly.
                    if (isset($sub_input['pivot'])) {
                        $ids[$id] = (array)$sub_input['pivot'];
                    } else {
                        $ids[] = $id;
                    }
                }

                // Sync to save pivot table and optional extra data.
                $relation->sync($ids);
                break;
            case HasManyThrough::class:
                //Assume that the input(s) contains a valid reference to the intermediate object
                //@TODO: Handle the relationship between this model and a "through" model created in the same fill operation
                //@TODO: (?) Handle chained HasManyThrough relationships, and save the models in the correct order
                foreach ($input as $sub_input) {
                    $relation_repository->setInput($sub_input)->save();
                }
                break;
        }
    }

    /**
     * Create a model.
     *
     * @return Model | Collection
     */
    public function create(): Model
    {
        $model_class = $this->getModelClass();
        $instance    = new $model_class();
        $this->fill($instance);

        return $instance;
    }

    /**
     * Create many models.
     *
     * @return Collection
     */
    public function createMany(): Collection
    {
        $collection = new Collection();

        foreach ($this->getInput() as $item) {
            $repository = clone $this;
            $repository->setInput($item);
            $collection->add($repository->create());
        }

        return $collection;
    }

    /**
     * Read a model.
     *
     * @param int|string|null $id
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function read($id = null): Model
    {
        return $this->findOrFail($id ?? $this->getInputId());
    }

    /**
     * Update a model.
     *
     * @param int|string|null $id
     *
     * @return Model|Collection
     */
    public function update($id = null): Model
    {
        $instance = $this->read($id);
        $this->fill($instance);

        return $this->read($instance->getKey());
    }

    /**
     * Updates many models.
     *
     * @return Collection
     */
    public function updateMany(): Collection
    {
        $collection = new Collection();

        foreach ($this->getInput() as $item) {
            $repository = clone $this;
            $repository->setInput($item);
            $collection->add($repository->update($repository->getInputId()));
        }

        return $collection;
    }

    /**
     * Delete a model.
     *
     * @param int|string|null $id
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function delete($id = null): bool
    {
        $instance = $this->read($id);

        return $instance->delete();
    }

    /**
     * Save a model, regardless of whether or not it is "new".
     *
     * @param int|string|null $id
     *
     * @return Model|Collection
     */
    public function save($id = null): Model
    {
        $id = $id ?? $this->getInputId();

        if ($id) {
            return $this->update($id);
        }

        return $this->create();
    }

    /**
     * Checks if the input has many items.
     *
     * @return bool
     */
    public function isManyOperation(): bool
    {
        return ($this->getInput() && array_keys($this->getInput()) === range(0, count($this->getInput()) - 1));
    }

    /**
     * A helper method for backwards compatibility.
     *
     * In laravel 5.4 they renamed the method `getPlainForeignKey` to `getForeignKeyName`
     *
     * @param HasOneOrMany $relation
     *
     * @return string
     */
    private function getRelationsForeignKeyName(HasOneOrMany $relation): string
    {
        return method_exists($relation, 'getForeignKeyName') ? $relation->getForeignKeyName() : $relation->getPlainForeignKey();
    }

    /**
     * Get the first element against the base query.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function first(): ?Model
    {
        return $this->transformModelForVisibility($this->query()->first());
    }

    /**
     * Get the first element against the base query, or fail if no results are found.
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFail(): Model
    {
        return $this->transformModelForVisibility($this->query()->firstOrFail());
    }

    protected function transformModelForVisibility(?Model $model): ?Model
    {
        if (is_null($model)) {
            return null;
        }

        if (empty($this->modify()->getPicks())) {
            return $model;
        }

        $visibleModelAttributeNames       = array_unique(array_merge($model->getVisible(), array_keys($model->toArray())));
        $pickedVisibleModelAttributeNames = array_intersect(array_merge($this->modify()->getEagerLoads(), $this->modify()->getPicks()), $visibleModelAttributeNames);

        //Hide all fields other than picked and includes, unless they were already hidden
        $model->setHidden(array_merge($model->getHidden(), $visibleModelAttributeNames));
        $model->makeVisible($pickedVisibleModelAttributeNames);

        return $model;
    }
}
