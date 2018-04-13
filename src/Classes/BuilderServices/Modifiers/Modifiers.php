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
                    $this->filters($value);
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
            if (!is_array($filter)) {
                $filter = [$key => $filter];
            }

            if (is_string($key)) {
                $this->filters[$key] = $filter;
            } else {
                $this->filters[] = $filter;
            }
        }

        return $this;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param array $available_filters
     */
    public function applyToBuilder(Builder $builder, array $available_filters = [])
    {
        if (!is_null($this->limit)) {
            $builder->take($this->limit < 0 ? 0 : $this->limit);

            $builder->skip($this->offset);
        }

        $this->applyFiltersToBuilder($builder, $this->filters, $available_filters);
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
}