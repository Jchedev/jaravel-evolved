<?php

namespace Jchedev\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;
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

            return $this->getRelatedObject(lcfirst($method_info[1]), array_get($parameters, 0, false));
        }

        /*
         * Proxy method to link an object through a relation.
         * The action will be selected based on the type of relation
         */
        if (preg_match('/^addAssociated(.*)$/', $method, $method_info) == 1) {
            return $this->setRelatedObject(lcfirst($method_info[1]), array_get($parameters, 0));
        }

        /*
         * Proxy method to link an object through a relation.
         * The action will be selected based on the type of relation
         */
        if (preg_match('/^compareAssociated(.*)$/', $method, $method_info) == 1) {
            return $this->compareRelatedObject(lcfirst($method_info[1]), array_get($parameters, 0));
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Set the attribute can contain the relation itself
     *
     * @param string $key
     * @param mixed $value
     * @return $this|bool|null
     */
    public function setAttribute($key, $value)
    {
        $relation = $this->getRelationObject($key);
        if (is_a($relation, BelongsTo::class)) {
            $relation->associate($value);

            return $this;
        }

        return parent::setAttribute($key, $value);
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
     * Return the relation matching the name or null if doesn't exist
     *
     * @param $relation_name
     * @return mixed
     */
    public function getRelationObject($relation_name)
    {
        if (method_exists($this, $relation_name) === false) {
            return null;
        }

        return $this->$relation_name();
    }

    /**
     * Get the object from a relation (based on $relation_name) or an empty object (if $return_empty_object = true)
     *
     * @param $relation_name
     * @param bool|false $return_empty_object
     * @return bool|mixed
     */
    protected function  getRelatedObject($relation_name, $return_empty_object = false)
    {
        $object_from_relation = ($this->relationLoaded($relation_name) ? $this->getRelation($relation_name) : $this->$relation_name);

        if (is_null($object_from_relation) && $return_empty_object !== false) {
            $object_from_relation = self::object($object_from_relation);
        }

        return $object_from_relation;
    }

    /**
     * Compare an object with the return of a relation
     *
     * @param $relation_name
     * @param \Illuminate\Database\Eloquent\Model $object
     * @return bool
     * @throws \Exception
     */
    protected function  compareRelatedObject($relation_name, \Illuminate\Database\Eloquent\Model $object)
    {
        $relation = $this->getRelationObject($relation_name);

        $relation_links = $this->relationLink($relation, $object);
        if (is_null($relation_links)) {
            throw new \Exception('CompareRelatedObject doesn\'t work on ' . get_class($relation) . ' relations yet');
        }

        foreach ($relation_links as $key => $value) {
            if ($this->$key != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Link the object (collection or model) to the relation based on the type of relation
     *
     * @param $relation_name
     * @param $object
     * @return array
     */
    protected function  setRelatedObject($relation_name, $object)
    {
        $relation = $this->getRelationObject($relation_name);

        switch (get_class($relation)) {

            case HasMany::class:
            case BelongsToMany::class:
                $objects = is_a($object, Collection::class) ? $object : collect(!is_array($object) ? [$object] : $object);
                $not_existing = $objects->diff($this->getRelatedObject($relation_name));

                $return = (count($not_existing) != 0) ? $relation->saveMany($not_existing->all()) : [];
                break;

            case HasOne::class:
                $return = $relation->save($object);
                break;

            case BelongsTo::class:
            case MorphTo::class:
                $return = $relation->associate($object);
                $this->save();
                break;
        }

        return $return;
    }

    /**
     * Return the links between a relation and a model (used for the comparison)
     *
     * @param Relation $relation
     * @param $link_to
     * @return array
     */
    protected function  relationLink(Relation $relation, $link_to)
    {
        if (is_a($link_to, Collection::class) || is_a($link_to, LengthAwarePaginator::class)) {
            $model_id = $link_to->modelKeys();
            $model_class = get_class($link_to->first());
        } else {
            $model_id = $link_to->id;
            $model_class = get_class($link_to);
        }

        switch (get_class($relation)) {

            case BelongsTo::class:
                return [
                    $relation->getForeignKey() => $model_id
                ];
                break;

            case MorphTo::class:
                return [
                    $relation->getMorphType()  => $model_class,
                    $relation->getForeignKey() => $model_id
                ];
                break;
        }
    }

    /**
     * Scope to link a relation directly
     *
     * @param $query
     * @param $relation_name
     * @param $object
     * @throws \Exception
     */
    public function     scopeWhereRelation($query, $relation_name, $object)
    {
        $relation_links = $this->relationLink($this->$relation_name(), $object);
        if (is_null($relation_links)) {
            throw new \Exception('WhereRelation doesn\'t work on ' . $relation_name . ' relations yet');
        }

        foreach ($relation_links as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, '=', $value);
            }
        }
    }

    /**
     * Create the orWhere relation part
     *
     * @param $query
     * @param $relation_name
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function     scopeOrWhereRelation($query, $relation_name, \Illuminate\Database\Eloquent\Model $object)
    {
        $query->orWhere(function ($join) use ($relation_name, $object) {
            $this->scopeWhereRelation($join, $relation_name, $object);
        });
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
        // Retrieve the namespace part of the current class
        $path_class_reference = static::class;
        if (($pos = strrpos($path_class_reference, '\\')) !== false) {
            $path_class_reference = substr($path_class_reference, 0, $pos);
        }
        $path_class_reference .= '\\';

        // Append the namespace with the basename of the object (or the $object_name if it is a string)
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