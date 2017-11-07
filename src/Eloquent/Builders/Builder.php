<?php

namespace Jchedev\Laravel\Eloquent\Builders;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class Builder extends EloquentBuilder
{
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
     * @param array|mixed $column
     * @param bool $add_select_all
     * @return $this
     */
    public function addSelect($column, $add_select_all = true)
    {
        if ($add_select_all && count($this->getQuery()->columns) == 0) {
            $this->select();
        }

        return parent::addSelect($column);
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

    /**
     * @param $relation_name
     * @param $fields
     * @return $this
     */
    public function addSelectOnRelation($relation_name, $fields)
    {
        $query = $this->getQueryRelation($relation_name);

        $this->joinOnRelation($relation_name, 'left');

        foreach ($fields as $key => $value) {

            if (is_a($value, Expression::class)) {
                $raw = $value;
            } else {
                $as = !is_int($key) ? $value : (str_replace('.', '_', $relation_name) . '_' . $value);

                $column = $query->from . '.' . (!is_int($key) ? $key : $value);

                $raw = DB::raw($column . ' as ' . $as);
            }

            $this->addSelect($raw);
        }

        return $this;
    }

    /**
     * @param $relation_name
     * @param string $type
     * @return $this
     */
    public function joinOnRelation($relation_name, $type = 'inner')
    {
        $query = $this->getQueryRelation($relation_name);

        $constraints = $query->wheres;

        $bindings = $query->bindings['where'];

        return $this->join($query->from, function (JoinClause $join) use ($constraints, $bindings) {

            $join->wheres = array_merge($join->wheres, $constraints);

            $join->addBinding($bindings);

        }, null, null, $type);
    }

    /**
     * @param $relation_name
     * @return \Illuminate\Database\Query\Builder
     */
    private function getQueryRelation($relation_name)
    {
        $clean_builder = $this->getModel()->newQuery();

        $clean_builder->has($relation_name);

        $has_wheres = $clean_builder->getQuery()->wheres;

        return $has_wheres[0]['query'];
    }
}