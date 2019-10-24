<?php

namespace Jchedev\Laravel\Eloquent\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Jchedev\Laravel\Eloquent\Builders\Builder;
use Jchedev\Laravel\Eloquent\Collections\Collection;

abstract class Model extends EloquentModel
{
    /*
     * New Methods
     */

    /**
     * Allow the call of getRouteKeyName() in a static way
     *
     * @return mixed
     */
    static function routeKeyName()
    {
        return with(new static)->getRouteKeyName();
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
     * Allow the call of getTableColumn() in a static way
     *
     * @param $column
     * @return mixed
     */
    static function tableColumn($column)
    {
        return with(new static)->getTableColumn($column);
    }

    /**
     * Allow the call of collection() in a static way
     *
     * @param array $models
     * @return mixed
     */
    static function collection(array $models = [])
    {
        return with(new static)->newCollection($models);
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
     * Because fireModelEvent is protected and cannot be called from outside the model
     * This is used by the JChedev\Eloquent\Builder - createMany() custom method
     *
     * @param $event
     * @return $this
     */
    public function applyEvent($event)
    {
        $this->fireModelEvent($event);

        return $this;
    }

    /**
     * Because updateTimestamps is protected and cannot be called from outside the model
     * This is used by the JChedev\Eloquent\Builder - createMany() custom method
     *
     * @return $this
     */
    public function applyTimestamps()
    {
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        return $this;
    }

    /**
     * Check if an attribute is set (different from null/empty)
     *
     * @param $attribute
     * @return bool
     */
    public function hasAttribute($attribute)
    {
        return array_key_exists($attribute, $this->attributes);
    }

    /*
     * Modified methods
     */

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
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasSetMutator($key) === false) {

            // If the value is an Eloquent\Model we most likely want to save the key
            if (is_a($value, EloquentModel::class)) {
                $value = $value->getKey();
            }
        }

        return parent::setAttribute($key, $value);
    }
}