<?php

namespace Jchedev\Laravel\Classes\BuilderServices;

use Illuminate\Database\Eloquent\Builder;
use Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers;
use Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator;

abstract class BuilderService
{
    /**
     * @var
     */
    public $identifier_key;

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    abstract function builder();

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function modifiedBuilder(Modifiers $modifiers = null)
    {
        $builder = $this->builder();

        return $this->modifyBuilder($builder, $modifiers);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function modifyBuilder(Builder $builder, Modifiers $modifiers = null)
    {
        if (!is_null($modifiers)) {
            $modifiers->applyToBuilder($builder);
        }

        return $builder;
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get(Modifiers $modifiers = null, $columns = ['*'])
    {
        $builder = $this->modifiedBuilder($modifiers);

        return $builder->get($columns);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @param array $columns
     * @return \Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator
     */
    public function paginate(Modifiers $modifiers = null, $columns = ['*'])
    {
        $builder = $this->modifiedBuilder($modifiers);

        $total = $builder->toBase()->getCountForPagination();

        $items = ($total != 0 ? $builder->get($columns) : $builder->getModel()->newCollection());

        $limit = !is_null($modifiers) ? $modifiers->getLimit() : null;

        $offset = !is_null($modifiers) ? $modifiers->getOffset() : 0;

        return new ByOffsetLengthAwarePaginator($items, $total, $limit, $offset);
    }

    /**
     * @param $id
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function find($id, Modifiers $modifiers = null, $columns = ['*'])
    {
        $builder = $this->modifiedBuilder($modifiers);

        $builder->where($this->identifierKey(), '=', $id);

        return $builder->first($columns);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function first(Modifiers $modifiers = null, $columns = ['*'])
    {
        $builder = $this->modifiedBuilder($modifiers);

        return $builder->first($columns);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @param string $columns
     * @return int
     */
    public function count(Modifiers $modifiers = null, $columns = '*')
    {
        $builder = $this->modifiedBuilder($modifiers);

        return $builder->count($columns);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @return string
     */
    public function toSql(Modifiers $modifiers = null)
    {
        $builder = $this->modifiedBuilder($modifiers);

        return $builder->toSql();
    }

    /**
     * @return string
     */
    public function identifierKey()
    {
        if (!is_null($this->identifier_key)) {
            return $this->identifier_key;
        }

        return $this->builder()->getModel()->getKeyName();
    }
}