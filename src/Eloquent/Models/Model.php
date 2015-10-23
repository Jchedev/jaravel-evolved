<?php

namespace Jchedev\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
         * Proxy method for $this->relation_name which return the query results of a relation. Can define first parameter as true, to always return an object even if NULL
         */
        if (preg_match('/^associated(.*)$/', $method, $method_info) == 1) {
            $relation_name = lcfirst($method_info[1]);
            $load_empty_object = array_get($parameters, 0, false);
            if (($found = $this->retrieveRelationObject($relation_name, $load_empty_object)) !== false) {
                return $found;
            }
        } else {
            /*
             * Proxy method to link an object through a relation. The action will be selected based on the type of relation
             */
            if (preg_match('/^addAssociated(.*)$/', $method, $method_info) == 1 && !is_null($object = array_get($parameters, 0))) {
                $relation_name = lcfirst($method_info[1]);

                return $this->addRelationObject($relation_name, $object);
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
     * Try to retrieve an object for a relation
     *
     * @param $relation_name
     * @param bool|false $return_empty_object
     * @return bool|mixed
     */
    private function    retrieveRelationObject($relation_name, $return_empty_object = false)
    {
        if (method_exists($this, $relation_name) === false) {
            return false;
        }

        $object_in_relation = $this->$relation_name;
        if (is_null($object_in_relation) && $return_empty_object === true) {
            $object_in_relation = Model::object($relation_name);
        }

        return $object_in_relation;
    }

    /**
     * Add a new object to a relation whatever the type of the relation is
     *
     * @param $relation_name
     * @param $object
     * @return null
     */
    private function    addRelationObject($relation_name, $object)
    {
        $return = null;

        if (method_exists($this, $relation_name) !== false) {
            $relation = $this->$relation_name();
            switch (get_class($relation)) {

                case BelongsToMany::class:
                    $return = $this->addBelongsToManyRelationObject($relation, collect(!is_array($object) ? [$object] : $object));
                    break;

                case MorphTo::class:
                    $return = $this->addMorphToRelationObject($relation, $object);
                    break;

                default:
                    // todo : we need to implement the other type of relations
                    exit;
                    break;
            }
        }

        if (!is_null($return)) {
            // todo : if we want to fire an event, this is from here!
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
     * @return bool
     */
    private function    addMorphToRelationObject(MorphTo $relation, \Illuminate\Database\Eloquent\Model $object)
    {
        $relation->associate($object);

        return $this->save();
    }

    /**
     * Return an object in the same namespace than the current class
     *
     * @param $object_name
     * @return mixed
     */
    static function object($object_name)
    {
        $path_class_reference = self::class;
        if (($pos = strrpos($path_class_reference, '\\')) !== false) {
            $path_class_reference = substr($path_class_reference, 0, $pos);
        }

        $path = $path_class_reference . '\\' . ucfirst($object_name);

        return new $path();
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