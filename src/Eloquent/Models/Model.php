<?php

namespace Jchedev\Laravel\Eloquent\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection as SupportCollection;
use Jchedev\Laravel\Eloquent\Builders\Builder;
use Jchedev\Laravel\Eloquent\Collections\Collection;

abstract class Model extends EloquentModel
{
    /**
     * Allow the call of getTable() in a static way
     *
     * @return mixed
     */
    static function table()
    {
        return with(new static)->getTable();
    }

    /**
     * Allow a static call on the getTableColumn() method
     *
     * @param $column
     * @return mixed
     */
    static function tableColumn($column)
    {
        return with(new static)->getTableColumn($column);
    }

    /**
     * Return the column concatenated to the table name
     *
     * @param $column
     * @return string
     */
    public function getTableColumn($column)
    {
        return table_column($this->getTable(), $column);
    }

    /**
     * Overwrite the Eloquent\Builder by a custom one with even more features
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Overwrite the Eloquent\Collection by a custom one with even more features
     *
     * @param array $models
     * @return \Jchedev\Laravel\Eloquent\Collections\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * @param array|string $relations
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function loadMissing($relations)
    {
        $relations = is_string($relations) ? func_get_args() : $relations;

        foreach ($relations as $key => $relation) {
            if ($this->relationLoaded($relation) === true) {
                unset ($relations[$key]);
            }
        }

        return parent::loadMissing($relations);
    }

    /**
     * Add the nested aspect to the relation load check
     *
     * @param string $key
     * @return bool
     */
    public function relationLoaded($key)
    {
        $test_nested_relation = explode('.', $key);

        if (count($test_nested_relation) > 1) {
            $relation = array_shift($test_nested_relation);

            if (parent::relationLoaded($relation)) {
                $relation = $this->getRelation($relation);

                if (is_a($relation, Collection::class)) {
                    $relation = $relation->first();
                }

                if (is_null($relation)) {
                    return true;
                }

                return $relation->relationLoaded(implode('.', $test_nested_relation));
            }

            return false;
        }

        return parent::relationLoaded($key);
    }

    /**
     * Magic method allowing the use of associatedXXXX() to access relation object
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $proxy_methods = [
            /*
             * Proxy method for $this->relation_name which return the query results of a relation.
             * Can define first parameter as true, to always return an object even if NULL
             */
            '/^associated(.*)$/'    => 'getAssociatedObject',

            /*
             * Proxy method to link an object through a relation.
             * The action will be selected based on the type of relation
             */
            '/^addAssociated(.*)$/' => 'addAssociatedObject'
        ];

        // Test each methods above to see if any match the name passed as parameter
        foreach ($proxy_methods as $regex => $method_name) {
            if (preg_match($regex, $method, $method_parameters)) {
                $parameters = array_merge(array_map('lcfirst', array_slice($method_parameters, 1)), $parameters);

                return call_user_func_array([$this, $method_name], $parameters);
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Get the object from a relation (based on $relation_name) or an empty object (if $return_empty_object = true)
     *
     * @param $relation_name
     * @param bool|false $return_empty_object
     * @return mixed
     * @throws \Exception
     */
    public function getAssociatedObject($relation_name, $return_empty_object = false)
    {
        $relation = $this->$relation_name();

        $result_from_relation = $this->getRelationValue($relation_name);

        if (is_null($result_from_relation) && $return_empty_object === true) {
            $result_from_relation = $relation->getRelated();
        }

        return $result_from_relation;
    }

    /**
     * Link the object (collection or model) to the relation based on the type of relation
     *
     * @param $relation_name
     * @param $object
     * @return array
     * @throws \Exception
     */
    public function addAssociatedObject($relation_name, $object)
    {
        $relation = $this->$relation_name();

        switch (get_class($relation)) {

            case HasMany::class:
                $collection = $object;

                if (!is_a($collection, SupportCollection::class)) {
                    $collection = collect(!is_array($object) ? [$object] : $object);
                }

                $return = $this->addHasManyAssociatedObject($relation, $collection);
                break;

            case HasOne::class:
                $return = $this->addHasOneAssociatedObject($relation, $object);
                break;

            case MorphTo::class:
            case BelongsTo::class:
                $return = $this->addBelongsToAssociatedObject($relation, $object);
                break;

            default:
                throw new \Exception('addAssociatedObject() is not working on this type of relation yet');
                break;
        }

        return $return;
    }

    /**
     * Add an object through a HasOne relation
     *
     * @param \Illuminate\Database\Eloquent\Relations\HasOne $relation
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function addHasOneAssociatedObject(HasOne $relation, \Illuminate\Database\Eloquent\Model $model)
    {
        return $relation->save($model);
    }

    /**
     * Add an object through a BelongsTo relation
     *
     * @param \Illuminate\Database\Eloquent\Relations\BelongsTo $relation
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    protected function addBelongsToAssociatedObject(BelongsTo $relation, \Illuminate\Database\Eloquent\Model $model)
    {
        $relation->associate($model);

        return $this->save();
    }

    /**
     * Add multiple objects through a HasMany relation
     *
     * @param \Illuminate\Database\Eloquent\Relations\HasMany $relation
     * @param \Illuminate\Support\Collection $objects
     * @return array|\Traversable
     */
    protected function addHasManyAssociatedObject(HasMany $relation, SupportCollection $objects)
    {
        return $relation->saveMany($objects);
    }
}