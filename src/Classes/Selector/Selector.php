<?php

namespace Jchedev\Laravel\Classes\Selector;

class Selector
{
    protected $builder;

    protected $limit = null;

    protected $offset = null;

    protected $filtering = [];

    protected $filters = [];

    protected $sorting = [];

    protected $sorts = [];

    /**
     * Selector constructor.
     *
     * @param $builder
     * @param array $filtering
     * @param array $sorting
     */
    public function __construct($builder, array $filtering = [], $sorting = [])
    {
        $this->setBuilder($builder);

        $this->setFilteringOptions($filtering);

        $this->setSortingOptions($sorting);
    }

    /**
     * @param $builder
     * @return $this
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @param array $filtering
     * @return $this
     */
    public function setFilteringOptions(array $filtering)
    {
        $this->filtering = $filtering;

        return $this;
    }

    /**
     * @param array $sorting
     * @return $this
     */
    public function setSortingOptions(array $sorting)
    {
        $this->sorting = $sorting;

        return $this;
    }

    /**
     * @param $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @param array $sorts
     * @return $this
     */
    public function setSorts(array $sorts)
    {
        $this->sorts = $sorts;

        return $this;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function applyFilters($builder)
    {
        foreach ($this->filters as $key => $value) {
            if (isset($this->filtering[$key])) {
                $filter = $this->filtering[$key];

                if (is_callable($filter)) {
                    $filter($builder, $value);
                }
            }
        }

        return $builder;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function applySorting($builder)
    {
        return $builder;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function applyPagination($builder)
    {
        if (!is_null($this->limit)) {
            $builder->limit($this->limit);

            $builder->offset($this->offset);
        }

        return $builder;
    }

    /**
     * @return mixed
     */
    public function getBuilder()
    {
        $builder = clone $this->builder;

        $this->applyFilters($builder);

        $this->applySorting($builder);

        $this->applyPagination($builder);

        return $builder;
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function get($columns = ['*'])
    {
        return $this->getBuilder()->get($columns);
    }

    /**
     * @param string $columns
     * @return mixed
     */
    public function count($columns = '*')
    {
        return $this->getBuilder()->count($columns);
    }
}