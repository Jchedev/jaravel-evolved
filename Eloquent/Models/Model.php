<?php

namespace Jchedev\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        if (preg_match('/^associated(.*)$/', $method, $method_info) == 1) {
            $relation_name = lcfirst($method_info[1]);
            $load_empty_object = array_get($parameters, 0, false);
            if (($found = $this->retrieveRelationObject($relation_name, $load_empty_object)) !== false) {
                return $found;
            }
        } else {
            if (preg_match('/^addAssociated(.*)$/', $method, $method_info) == 1 && !is_null($object = array_get($parameters, 0))) {
                $relation_name = lcfirst($method_info[1]);

                return $this->addRelationObject($relation_name, $object);
            }
        }

        return parent::__call($method, $parameters);
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
                    $objects = collect(!is_array($object) ? [$object] : $object);

                    $not_existing = $objects->diff($this->retrieveRelationObject($relation_name));
                    if (count($not_existing) != 0) {
                        $return = $relation->saveMany($not_existing->all());
                    }
                    unset($objects, $not_existing);
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
     * Return an object in the same namespace than now
     *
     * @param $object
     * @return mixed
     */
    static function object($object)
    {
        $path = __NAMESPACE__ . '\\' . $object;

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
        if (is_a($column, \Illuminate\Database\Query\Expression::class)) {
            return $column;
        }

        return \DB::raw('`' . $this->getTable() . '`.' . ($column != '*' ? '`' . $column . '`' : $column));
    }
}