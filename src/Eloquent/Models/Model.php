<?php

namespace Jchedev\Laravel\Eloquent\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
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
}