<?php

namespace Jchedev\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Jchedev\Eloquent\Builders\Builder;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    /**
     * Magic method allowing the use of associatedXXXX() to access relation object
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        /*
         * Proxy method for $this->relation_name which return the query results of a relation.
         * Can define first parameter as true, to always return an object even if NULL
         */
        if (preg_match('/^associated(.*)$/', $method, $method_info) == 1) {
            $load_empty_object = array_get($parameters, 0, false);
            if (($found = $this->getRelatedObject($method_info[1], $load_empty_object)) !== false) {
                return $found;
            }
        } else {
            /*
             * Proxy method to link an object through a relation.
             * The action will be selected based on the type of relation
             */
            if (preg_match('/^addAssociated(.*)$/', $method, $method_info) == 1 && !is_null($object = array_get($parameters, 0))) {
                $execute_save_too = array_get($parameters, 0, true);

                return $this->setRelatedObject($method_info[1], $object, $execute_save_too);
            }
        }

        return parent::__call($method, $parameters);
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
     * Implements the concept of load on the model itself (why only collection??)
     *
     * @param array|string $relations
     * @return $this
     */
    public function load($relations)
    {
        $relations = (array)$relations;
        foreach ($relations as $relation) {
            $this->getRelatedObject($relation);
        }

        return $this;
    }

    /**
     * Scope to link a relation directly
     *
     * @param $query
     * @param $relation_name
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function     scopeWhereRelation($query, $relation_name, \Illuminate\Database\Eloquent\Model $model)
    {
        $relation_name = lcfirst($relation_name);
        if (method_exists($this, $relation_name) === false) {
            return false;
        }

        $relation = $this->$relation_name();
        switch (get_class($relation)) {

            case BelongsTo::class:
                $query->where($relation->getForeignKey(), '=', $model->id);
                break;

            case MorphTo::class:
                $query->where($relation->getMorphType(), '=', get_class($model));
                $query->where($relation->getForeignKey(), '=', $model->id);
                break;
        }
    }

    /**
     * Create the orWhere relation part
     *
     * @param $query
     * @param $relation_name
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function scopeOrWhereRelation($query, $relation_name, \Illuminate\Database\Eloquent\Model $model)
    {
        $query->orWhere(function ($join) use ($relation_name, $model) {
            $this->scopeWhereRelation($join, $relation_name, $model);
        });
    }

    /**
     * Try to retrieve an object for a relation
     *
     * @param $relation_name
     * @param bool|false $return_empty_object
     * @return bool|mixed
     */
    private function    getRelatedObject($relation_name, $return_empty_object = false)
    {
        $relation_name = lcfirst($relation_name);
        if (method_exists($this, $relation_name) === false) {
            return false;
        }

        $object_in_relation = ($this->relationLoaded($relation_name) ? $this->getRelation($relation_name) : $this->$relation_name);
        if (is_null($object_in_relation) && $return_empty_object === true) {
            $object_in_relation = self::object($relation_name);
        }

        return $object_in_relation;
    }

    /**
     * Add a new object to a relation whatever the type of the relation is
     *
     * @param $relation_name
     * @param $object
     * @param bool|true $execute_save
     * @return array|bool|null
     */
    private function    setRelatedObject($relation_name, $object, $execute_save = true)
    {
        $relation_name = lcfirst($relation_name);
        if (method_exists($this, $relation_name) === false) {
            return false;
        }

        $relation = $this->$relation_name();
        switch (get_class($relation)) {

            case BelongsToMany::class:
                $objects = is_a($object, Collection::class) ? $object : collect(!is_array($object) ? [$object] : $object);
                $return = $this->addBelongsToManyRelationObject($relation, $objects);
                break;

            case BelongsTo::class:
                // todo
                break;

            case MorphOne::class:
                // todo
                break;

            case MorphMany::class:
                // todo
                break;

            case MorphTo::class:
                $return = $this->addMorphToRelationObject($relation, $object, $execute_save);
                break;

            case HasOne::class:
                // todo
                break;

            case HasMany::class;
                // todo
                break;

            default:
                $return = null;
                break;
        }

        return $return;
    }

    /**
     * Add a new collection of objects to a BelongsToMany collection (if the object is not already associated
     *
     * @param BelongsToMany $relation
     * @param Collection $objects
     * @return array|null
     */
    private function    addBelongsToManyRelationObject(BelongsToMany $relation, Collection $objects)
    {
        $return = null;
        $relation_name = $relation->getRelationName();

        $not_existing = $objects->diff($this->retrieveRelationObject($relation_name));
        if (count($not_existing) != 0) {
            $return = $relation->saveMany($not_existing->all());
        }

        unset($objects, $not_existing);

        return $return;
    }

    /**
     * Add a new object for a morphTo relation
     *
     * @param MorphTo $relation
     * @param \Illuminate\Database\Eloquent\Model $object
     * @param bool|true $save
     * @return bool|null
     */
    private function    addMorphToRelationObject(MorphTo $relation, \Illuminate\Database\Eloquent\Model $object, $save = true)
    {
        $relation->associate($object);

        return ($save === true) ? $this->save() : null;
    }

    /**
     * Return the correct relations based on an array of values
     *
     * @param $values
     * @param null $prepend
     * @return array
     */
    static function relations($values, $prepend = null)
    {
        $values = (!is_array($values) ? [$values] : $values);

        if (!is_null($prepend)) {
            foreach ($values as $key => $value) {
                $values[$key] = $prepend . '.' . $value;
            }
        }

        return $values;
    }

    /**
     * Return an object in the same namespace than the current class
     *
     * @param $object_name
     * @return mixed
     */
    static function object($object_name = null)
    {
        $path = self::classPath($object_name);

        return class_exists($path) ? new $path() : null;
    }

    /**
     * Return the ClassPath for an object (or the current one)
     *
     * @param null $object_name
     * @return string
     */
    static function classPath($object_name = null)
    {
        $path_class_reference = static::class;
        if (($pos = strrpos($path_class_reference, '\\')) !== false) {
            $path_class_reference = substr($path_class_reference, 0, $pos);
        }
        $path_class_reference .= '\\';

        if (!is_null($object_name)) {
            $path_class_reference .= ucfirst($object_name);
        } else {
            $path_class_reference .= get_basename_class(static::class);
        }

        return $path_class_reference;
    }

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
        if (is_a($column, Expression::class)) {
            return $column;
        }

        return \DB::raw('`' . $this->getTable() . '`.' . ($column != '*' ? '`' . $column . '`' : $column));
    }
}