<?php

namespace Jchedev\Laravel\Eloquent\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Jchedev\Laravel\Eloquent\Builders\Builder;
use Jchedev\Laravel\Eloquent\Collections\Collection;

abstract class Model extends EloquentModel
{
    /**
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
     * @param $event
     * @return $this
     */
    public function applyEvent($event)
    {
        $this->fireModelEvent($event);

        return $this;
    }

    /**
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
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasSetMutator($key) === false && is_a($value, \Illuminate\Database\Eloquent\Model::class)) {
            $value = $value->getKey();
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function hasAttribute($attribute)
    {
        return array_key_exists($attribute, $this->attributes);
    }
}