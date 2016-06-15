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
    protected $relations_count = [];

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
    static function  tableColumn($column)
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
     * A BelongsTo relation can set using the dynamic setter (still need to be fillable) and boolean are automatically casted
     *
     * @param string $key
     * @param mixed $value
     * @return $this|bool|null
     */
    public function setAttribute($key, $value)
    {
        if (method_exists($this, 'set' . ucfirst(camel_case($key)) . 'Attribute') === false) {

            if (method_exists($this, $key) === true) {
                $relation = $this->$key();

                if (is_a($relation, BelongsTo::class)) {
                    $relation->associate($value);

                    return $this;
                }
            }

            if (array_get($this->casts, $key) == 'boolean') {
                $value = ($value === true ? 1 : ($value === false ? 0 : $value));
            }
        }

        return parent::setAttribute($key, $value);
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
            '/^associated(.*)$/'      => 'getAssociatedObject',

            /*
             * Proxy method to link an object through a relation.
             * The action will be selected based on the type of relation
             */
            '/^addAssociated(.*)$/'   => 'addAssociatedObject',

            /*
             * Proxy method to count objects through a relation.
             * The action will be selected based on the type of relation
             */
            '/^countAssociated(.*)$/' => 'countAssociatedObject'
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

    /*
    * ------> Relation methods (add, get, remove) <-------
    */

    /**
     * Get the object from a relation (based on $relation_name) or an empty object (if $return_empty_object = true)
     *
     * @param $relation_name
     * @param bool|false $return_empty_object
     * @return mixed
     * @throws \Exception
     */
    public function  getAssociatedObject($relation_name, $return_empty_object = false)
    {
        $relation = $this->$relation_name();

        $result_from_relation = $this->getRelationValue($relation_name);

        if (is_null($result_from_relation) && $return_empty_object === true) {
            $result_from_relation = $relation->getRelated();
        }

        return $result_from_relation;
    }

    /**
     * Save the value of a count for an associated object
     *
     * @param $relation_name
     * @param $value
     * @return $this
     */
    public function saveCountAssociatedObject($relation_name, $value)
    {
        $this->relations_count[$relation_name] = $value;

        return $this;
    }

    /**
     * Count the number of models associated through a relation (and cache it)
     *
     * @param $relation_name
     * @return mixed
     */
    public function  countAssociatedObject($relation_name)
    {
        if (is_null(array_get($this->relations_count, $relation_name))) {
            $this->relations_count[$relation_name] = $this->$relation_name()->count();
        }

        return $this->relations_count[$relation_name];
    }

    /**
     * Link the object (collection or model) to the relation based on the type of relation
     *
     * @param $relation_name
     * @param $object
     * @return array
     * @throws \Exception
     */
    public function  addAssociatedObject($relation_name, $object)
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

        $this->saveCountAssociatedObject($relation_name, null);

        return $return;
    }

    /**
     * Add an object through a HasOne relation
     *
     * @param \Illuminate\Database\Eloquent\Relations\HasOne $relation
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function  addHasOneAssociatedObject(HasOne $relation, \Illuminate\Database\Eloquent\Model $model)
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
    protected function  addBelongsToAssociatedObject(BelongsTo $relation, \Illuminate\Database\Eloquent\Model $model)
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
    protected function  addHasManyAssociatedObject(HasMany $relation, SupportCollection $objects)
    {
        return $relation->saveMany($objects);
    }
}