<?php

namespace Jchedev\Laravel\Classes\BuilderServices\Modifiers;

use Illuminate\Database\Eloquent\Builder;

class Modifiers
{
    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var  array
     */
    private $filters = [];

    /**
     * @var  array
     */
    private $sort = [];

    /**
     * Modifiers constructor.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->set($params);
    }

    /**
     * @param array $params
     * @return $this
     */
    public function set(array $params)
    {
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'offset':
                    $this->offset($value);
                    break;
                case 'limit':
                    $this->limit($value);
                    break;
                case 'filters':
                    if (is_array($value)) {
                        $this->filters($value);
                    }
                    break;
                case 'sort':
                    $this->sort($value, array_get($params, 'sort_order'));
                    break;
            }
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset ?: 0;
    }

    /**
     * @param null $offset
     * @return $this
     */
    public function offset($offset = null)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param null $limit
     * @return $this
     */
    public function limit($limit = null)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function filters(array $filters)
    {
        foreach ($filters as $key => $filter) {
            if (is_string($filter)) {
                $filter = [$key => $filter];
            }

            $this->filters[] = $filter;
        }

        return $this;
    }

    /**
     * @param $sort
     * @param null $order
     * @return $this
     */
    public function sort($sort, $order = null)
    {
        if (!is_array($sort)) {
            $this->sort[$sort] = in_array($order, ['asc', 'desc']) ?: 'asc';
        }

        return $this;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param array $available_filters
     * @param array $available_sort
     */
    public function applyToBuilder(Builder $builder, array $available_filters = [], array $available_sort = [])
    {
        if (!is_null($this->limit)) {
            $builder->take($this->limit < 0 ? 0 : $this->limit);

            $builder->skip($this->offset);
        }

        $this->applyFiltersToBuilder($builder, $this->filters, $available_filters);

        $this->applySortToBuilder($builder, $this->sort, $available_sort);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param array $filters_values
     * @param array $available_filters
     */
    public function applyFiltersToBuilder(Builder $builder, array $filters_values, array $available_filters)
    {
        foreach ($filters_values as $filter) {
            $builder->where(function ($builder) use ($filter, $available_filters) {

                foreach ($filter as $key => $value) {
                    $closure = array_get($available_filters, $key);

                    if (is_callable($closure)) {
                        $closure($builder, $value);
                    }
                }
            });
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param array $sort_values
     * @param array $available_sort
     */
    public function applySortToBuilder(Builder $builder, array $sort_values, array $available_sort)
    {
        foreach ($sort_values as $sort => $direction) {
            $closure = array_get($available_sort, $sort);

            if (is_string($closure)) {
                $builder->orderBy($closure, $direction);
            } elseif (is_callable($closure)) {
                $closure($builder, $direction);
            }
        }
    }
}