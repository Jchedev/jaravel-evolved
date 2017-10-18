<?php

namespace Jchedev\Laravel\Eloquent\Builders;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Builder extends EloquentBuilder
{
    /**
     * We automatically select all columns for the model to simplify some logic around select/addSelect
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        parent::setModel($model);

        $this->select('*');

        return $this;
    }

    /**
     * Attach the name of the table to each column of the select if possible
     *
     * @param array $columns
     * @return mixed
     */
    public function select($columns = ['*'])
    {
        $columns = !is_array($columns) ? [$columns] : $columns;

        foreach ($columns as $key => $column) {
            $columns[$key] = $this->getModelTableColumn($column);
        }

        return parent::select($columns);
    }

    /**
     * Overwrite the where method to add the table name in front of the column
     *
     * @param string $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        return parent::where($this->getModelTableColumn($column), $operator, $value, $boolean);
    }

    /**
     * Overwrite the whereNull method to add the table name in front of the column
     *
     * @param $column
     * @param string $boolean
     * @param bool|false $not
     * @return mixed
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        return parent::whereNull($this->getModelTableColumn($column), $boolean, $not);
    }

    /**
     * Overwrite the whereIn method to add the table name in front of the column
     *
     * @param $column
     * @param $values
     * @param string $boolean
     * @param bool|false $not
     * @return mixed
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        return parent::whereIn($this->getModelTableColumn($column), $values, $boolean, $not);
    }

    /**
     * Add a new whereIs method to let the builder check against a model
     *
     * @param $value
     * @param string $boolean
     * @param bool $not
     * @return mixed
     */
    public function whereIs($value, $boolean = 'and', $not = false)
    {
        if (is_a($value, Collection::class)) {
            $value = $value->modelKeys();
        } elseif (is_a($value, Model::class)) {
            $value = $value->getKey();
        }

        return $this->whereIn($this->getModel()->getKeyName(), (array)$value, $boolean, $not);
    }

    /**
     * Overwrite the whereBetween method to add the table name in front of the column
     *
     * @param $column
     * @param array $values
     * @param string $boolean
     * @param bool|false $not
     * @return mixed
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        return parent::whereBetween($this->getModelTableColumn($column), $values, $boolean, $not);
    }

    /**
     * This count keeps in mind the limit applied to the query
     *
     * @param string $columns
     * @return int
     */
    public function countWithLimit($columns = '*')
    {
        $parent_count = $this->count($columns);

        $limit = $this->getQuery()->limit;

        return (!is_null($limit) && $limit < $parent_count) ? $limit : $parent_count;
    }

    /**
     * Call the helper available in the package to generates the name of the column with the table name attached
     *
     * @param $column
     * @return mixed
     */
    public function getModelTableColumn($column)
    {
        return table_column($this->getModel()->getTable(), $column);
    }
}