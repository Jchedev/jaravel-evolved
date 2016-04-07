<?php

namespace Jchedev\Laravel\Eloquent\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Jchedev\Laravel\Eloquent\Builders\Builder;

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

    /*
     * ------> Laravel methods overwritten <-------
     */

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
            '/^associated(.*)$/'        => 'getAssociatedObject',

            /*
             * Proxy method to link an object through a relation.
             * The action will be selected based on the type of relation
             */
            '/^addAssociated(.*)$/'     => 'addAssociatedObject',

            /*
             * Proxy method to link an object through a relation.
             * The action will be selected based on the type of relation
             */
            '/^removeAssociated(.*)$/'  => 'removeAssociatedObject',

            /*
             * Proxy method to link an object through a relation.
             * The action will be selected based on the type of relation
             */
            '/^compareAssociated(.*)$/' => 'compareAssociatedObject'
        ];

        foreach ($proxy_methods as $regex => $method_name) {
            if (preg_match($regex, $method, $method_parameters)) {
                $parameters = array_merge(array_slice($method_parameters, 1), $parameters);

                return call_user_func_array([$this, $method_name], $parameters);
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * A BelongsTo relation can be set using the dynamic setter
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
     * Overwrite the Eloquent\Builder by a custom one with even more features
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /*
     * ------> Relation methods (add, get, remove, compare) <-------
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
        $relation_name = lcfirst($relation_name);
        $relation = $this->$relation_name();

        $result_from_relation = $this->getRelationValue($relation_name);

        if (is_null($result_from_relation) && $return_empty_object === true) {
            $result_from_relation = $relation->getRelated();
        }

        return $result_from_relation;
    }

    /**
     * Compare an object with the return of a relation
     *
     * @param $relation_name
     * @param $object
     * @return bool
     * @throws \Exception
     */
    public function  compareAssociatedObject($relation_name, $object)
    {
        $relation_name = lcfirst($relation_name);
        $relation = $this->$relation_name();

        $relation_links = $this->relationLinksTo($relation, $object);
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
     * @throws \Exception
     */
    public function  addAssociatedObject($relation_name, $object)
    {
        $relation_name = lcfirst($relation_name);
        $relation = $this->$relation_name();

        switch (get_class($relation)) {

            case HasMany::class:
            case BelongsToMany::class:
                $objects = is_a($object, Collection::class) ? $object : collect(!is_array($object) ? [$object] : $object);
                $not_existing = $objects->diff($this->getAssociatedObject($relation_name));

                $return = collect((count($not_existing) != 0) ? $relation->saveMany($not_existing->all()) : []);
                break;

            case HasOne::class:
                $return = $relation->save($object);
                break;

            case BelongsTo::class:
            case MorphTo::class:
                $relation->associate($object);
                $return = $this->save();
                break;

            default:
                throw new \Exception('addAssociatedObject() don\'t work on this type of relation yet');
                break;
        }

        return $return;
    }

    /**
     * Remove the associated elements (collection or model). Different than deleted them
     *
     * @param $relation_name
     * @param $object
     * @return array
     * @throws \Exception
     */
    public function  removeAssociatedObject($relation_name, $object)
    {
        $relation_name = lcfirst($relation_name);
        $relation = $this->$relation_name();

        switch (get_class($relation)) {

            case HasMany::class:
                $objects = is_a($object, Collection::class) ? $object : collect(!is_array($object) ? [$object] : $object);
                $return = (count($objects) != 0) ? $relation->whereIn('id', $objects->modelKeys())->delete() : [];
                break;

            case BelongsToMany::class:
                $objects = is_a($object, Collection::class) ? $object : collect(!is_array($object) ? [$object] : $object);
                $return = (count($objects) != 0) ? $relation->detach($objects->modelKeys()) : [];
                break;

            default:
                throw new \Exception('removeAssociatedObject() don\'t work on this type of relation yet');
                break;
        }

        return $return;
    }

    /**
     * Return the values used for a relation
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param $link_to
     * @return array
     * @throws \Exception
     */
    protected function  relationLinksTo(Relation $relation, $link_to)
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

            default:
                throw new \Exception('WhereRelation() don\'t work on this type of relation yet');
                break;
        }
    }

    /*
     * ------> Scopes for Builder <-------
     */

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
        $relation_links = $this->relationLinksTo($this->$relation_name(), $object);

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
}