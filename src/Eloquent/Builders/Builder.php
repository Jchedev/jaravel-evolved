<?php

namespace Jchedev\Eloquent\Builders;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Builder extends EloquentBuilder
{
    /**
     * Add a Select * on the model when set
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        parent::setModel($model);

        $this->select($this->getModelTableColumn('*'));

        return $this;
    }

    /**
     * Method randomizing the selection returned
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
     * If the model associated has the method getTableColumn (needs to inherits from \Jchedev\Eloquent\Model), then use it
     *
     * @param $column
     * @return mixed
     */
    public function  getModelTableColumn($column)
    {
        return table_column($this->getModel()->getTable(), $column);
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
     * Overwrite the whereIn method to add the table name in front of the column
     *
     * @param $column
     * @param array $in
     * @return mixed
     */
    public function whereIn($column, Array $in)
    {
        return parent::whereIn($this->getModelTableColumn($column), $in);
    }

    /**
     * Overwrite the whereBetween method to add the table name in front of the column
     *
     * @param $column
     * @param array $in
     * @return mixed
     */
    public function whereBetween($column, Array $in)
    {
        return parent::whereBetween($this->getModelTableColumn($column), $in);
    }

    /**
     * Merge another builder into a sub "SELECT()" to this builder
     *
     * @param \Illuminate\Database\Query\Builder $builder
     * @param null $as
     * @return $this
     */
    public function addSelectFromBuilder(\Illuminate\Database\Query\Builder $builder, $as = null)
    {
        $sql = '(' . $builder->toSql() . ')';
        if (!is_null($as)) {
            $sql .= ' as ' . $as;
        }

        $this->addSelect(\DB::raw($sql));

        foreach ($builder->getBindings() as $binding) {
            $this->addBinding($binding, 'select');
        }

        return $this;
    }

    /**
     * New method to execute a count() but keeping the limit in mind. Laravel should probably do that in fact.
     *
     * @return int
     */
    public function countWithLimit()
    {
        $parent_count = $this->count();

        $limit = $this->getQuery()->limit;

        return (!is_null($limit) && $limit < $parent_count) ? $limit : $parent_count;
    }
}