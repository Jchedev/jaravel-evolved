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
    /*
     * New Methods
     */

    /**
     * @param array $arrayOfAttributes
     * @return array|\Illuminate\Database\Eloquent\Collection
     */
    public function createMany(array $arrayOfAttributes = [])
    {
        $collection = $this->getModel()->newCollection([]);

        foreach ($arrayOfAttributes as $key => $attributes) {
            $newInstance = $this->newModelInstance($attributes);

            if (method_exists($newInstance, 'applyEvent')) {
                $newInstance->applyEvent('creating');
            }

            if (method_exists($newInstance, 'applyTimestamps')) {
                $newInstance->applyTimestamps();
            }

            $arrayOfAttributes[$key] = $newInstance->getAttributes();

            $collection->push($newInstance);
        }

        $this->insert($arrayOfAttributes);

        $lastId = $this->getConnection()->getPdo()->lastInsertId();

        foreach ($collection as $model) {
            if ($model->getIncrementing() && !is_null($lastId)) {
                $model->setAttribute($model->getKeyName(), $lastId++);
            }

            $model->wasRecentlyCreated = true;

            $model->exists = true;

            $model->syncOriginal();
        }

        return $collection;
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
        if ($value instanceof Collection) {
            $value = $value->modelKeys();
        } elseif ($value instanceof Model) {
            $value = $value->getKey();
        }

        return $this->whereIn($this->getModel()->getKeyName(), (array)$value, $boolean, $not);
    }

    /**
     * This count keeps in mind the limit applied to the query
     *
     * @param string $columns
     * @return int
     */
    public function countWithLimit($columns = '*')
    {
        $parentCount = $this->count($columns);

        $limit = $this->getQuery()->limit;

        return (!is_null($limit) && $limit < $parentCount) ? $limit : $parentCount;
    }

    /**
     * This chunk keeps in mind the limit applied to the query
     *
     * @param $count
     * @param callable $callback
     * @param null $limit
     * @return bool
     */
    public function chunkWithLimit($count, callable $callback, $limit = null)
    {
        $total = 0;

        if (!is_null($limit) && $limit < $count) {
            $count = $limit;
        }

        $this->chunk($count, function ($elements) use ($callback, &$total, $limit) {
            $callback($elements);

            $total += count($elements);

            if (!is_null($limit) && $total >= $limit) {
                return false;
            }
        });

        return true;
    }

    /**
     * @param $relationName
     * @param $fields
     * @return $this
     */
    public function addSelectThroughRelation($relationName, $fields)
    {
        $query = $this->getQueryRelation($relationName);

        $this->joinThroughRelation($relationName, 'left');

        foreach ($fields as $key => $value) {

            if (is_a($value, Expression::class)) {
                $raw = $value;
            } else {
                $as = !is_int($key) ? $value : (str_replace('.', '_', $relationName) . '_' . $value);

                $column = $query->from . '.' . (!is_int($key) ? $key : $value);

                $raw = DB::raw($column . ' as ' . $as);
            }

            $this->addSelect($raw);
        }

        return $this;
    }

    /**
     * @param $relationName
     * @param string $type
     * @return $this
     */
    public function joinThroughRelation($relationName, $type = 'inner')
    {
        $query = $this->getQueryRelation($relationName);

        $constraints = $query->wheres;

        $bindings = $query->bindings['where'];

        return $this->join($query->from, function (JoinClause $join) use ($constraints, $bindings) {

            $join->wheres = array_merge($join->wheres, $constraints);

            $join->addBinding($bindings);

        }, null, null, $type);
    }

    /*
     * Modified methods
     */

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
            $columns[$key] = $this->qualifyColumn($column);
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
     * @return mixed
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        return parent::where($this->qualifyColumn($column), $operator, $value, $boolean);
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
        return parent::whereNull($this->qualifyColumn($column), $boolean, $not);
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
        return parent::whereIn($this->qualifyColumn($column), $values, $boolean, $not);
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
        return parent::whereBetween($this->qualifyColumn($column), $values, $boolean, $not);
    }

    /*
     * Private Methods
     */

    /**
     * Used by addSelectThroughRelation() and joinThroughRelation to find the proper wheres to use
     *
     * @param $relation_name
     * @return \Illuminate\Database\Query\Builder
     */
    private function getQueryRelation($relation_name)
    {
        $cleanBuilder = $this->getModel()->newQuery();

        $cleanBuilder->has($relation_name);

        $hasWheres = $cleanBuilder->getQuery()->wheres;

        return $hasWheres[0]['query'];
    }
}