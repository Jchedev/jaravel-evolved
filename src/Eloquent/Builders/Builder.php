<?php

namespace Jchedev\Laravel\Eloquent\Builders;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Builder extends EloquentBuilder
{
    protected $counts = [];

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
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'])
    {
        $this->addCountSub();

        return parent::get($columns)->map(function ($element) {

            foreach ($this->counts as $relation_name => $saved_as) {
                $element->saveCountAssociatedObject($relation_name, $element[$saved_as]);

                unset($element[$saved_as]);
            }

            return $element;
        });
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
     * Sort the results in a randomized order
     *
     * @return $this
     */
    public function randomize()
    {
        $this->orderBy(DB::raw('RAND()'));

        return $this;
    }

    /**
     * Could sounds weird but sometimes we want to make sure that the builder will return nothing
     *
     * @return $this
     */
    public function forceFail()
    {
        $this->where(DB::raw('1 = 2'));

        return $this;
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
     * Save the list of relations to add as a count
     *
     * @param $relations
     * @return $this
     */
    public function withCount($relations)
    {
        foreach ((array)$relations as $relation) {
            $this->counts[$relation] = 'computed_count_' . $relation;
        }

        return $this;
    }

    /**
     * Call the helper available in the package to generates the name of the column with the table name attached
     *
     * @param $column
     * @return mixed
     */
    public function  getModelTableColumn($column)
    {
        return table_column($this->getModel()->getTable(), $column);
    }

    /**
     * Add the subqueries to count easily defined relations
     *
     * @return array
     */
    private function  addCountSub()
    {
        foreach ($this->counts as $relation => $save_count_as) {
            $related = $this->getModel()->$relation();

            $this->selectSub(
                $related
                    ->getModel()
                    ->select(DB::raw('COUNT(id)'))
                    ->where(
                        $related->getForeignKey(),
                        '=',
                        DB::raw($related->getQualifiedParentKeyName())
                    )
                    ->toBase(),
                $save_count_as
            );
        }

        return $this;
    }
}