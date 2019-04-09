<?php

namespace Jchedev\Laravel\Classes\Selector;

use Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator;

class Selector
{
    protected $originalBuilder;

    protected $modifiedBuilder;

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
        $this->originalBuilder = $builder;

        return $this->clearModifiedBuilder();
    }

    /**
     * @param array $filtering
     * @return $this
     */
    public function setFilteringOptions(array $filtering)
    {
        $this->filtering = $filtering;

        return $this->clearModifiedBuilder();
    }

    /**
     * @param array $sorting
     * @return $this
     */
    public function setSortingOptions(array $sorting)
    {
        $this->sorting = $sorting;

        return $this->clearModifiedBuilder();
    }

    /**
     * @param $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this->clearModifiedBuilder();
    }

    /**
     * @param $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this->clearModifiedBuilder();
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;

        return $this->clearModifiedBuilder();
    }

    /**
     * @param array $sorts
     * @return $this
     */
    public function setSorts(array $sorts)
    {
        $newSorts = [];

        foreach ($sorts as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (is_integer($key)) {
                $newSorts[$value] = 'asc';
            } else {
                $direction = strtolower($value);

                $newSorts[$key] = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
            }
        }

        $this->sorts = $newSorts;

        return $this->clearModifiedBuilder();
    }

    /**
     * @return $this
     */
    protected function clearModifiedBuilder()
    {
        $this->modifiedBuilder = null;

        return $this;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function applyFilters($builder, array $filters)
    {
        foreach ($filters as $key => $value) {
            if (isset($this->filtering[$key])) {
                $filter = $this->filtering[$key];

                if (is_callable($filter)) {
                    if ($filter($builder, $value) === false) {
                        $builder->willFail = true;
                    }
                }
            }
        }

        return $builder;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function applySorting($builder, array $sorts)
    {
        foreach ($sorts as $key => $direction) {
            if (isset($this->sorting[$key])) {
                $sort = $this->sorting[$key];

                if (is_callable($sort)) {
                    $sort($builder, $direction);
                }
            }
        }

        return $builder;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function applyPagination($builder, $limit = null, $offset = null)
    {
        if (!is_null($limit)) {
            $builder->limit($limit);

            if (!is_null($offset)) {
                $builder->offset($offset);
            }
        }

        return $builder;
    }

    /**
     * @return mixed
     */
    public function getBuilder()
    {
        if (is_null($this->modifiedBuilder)) {
            $builder = clone $this->originalBuilder;

            $this->applyFilters($builder, $this->filters);

            $this->applySorting($builder, $this->sorts);

            $this->modifiedBuilder = $builder;
        }

        return $this->modifiedBuilder;
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function get($columns = ['*'])
    {
        $builder = clone $this->getBuilder();

        if (data_get($builder, 'willFail') === true) {
            return collect();
        }

        return $this->applyPagination($builder, $this->limit, $this->offset)->get($columns);
    }

    /**
     * @param null $limit
     * @param null $offset
     * @param array $columns
     * @return \Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator
     */
    public function paginateByOffset($limit = null, $offset = null, $columns = ['*'])
    {
        $builder = clone $this->getBuilder();

        $limit = !is_null($limit) ? $limit : (!is_null($this->limit) ? $this->limit : 15);

        $offset = !is_null($offset) ? $offset : $this->offset;

        if (data_get($builder, 'willFail') === true) {
            $items = collect();

            $itemsTotal = 0;
        } else {
            $items = $this->applyPagination($builder, $limit, $offset)->get($columns);

            $itemsTotal = $builder->toBase()->getCountForPagination();
        }

        return new ByOffsetLengthAwarePaginator($items, $itemsTotal, $limit, $offset);
    }

    /**
     * @param string $columns
     * @return mixed
     */
    public function count($columns = '*')
    {
        $builder = $this->getBuilder();

        if (data_get($builder, 'willFail') === true) {
            return 0;
        }

        return $builder->count($columns);
    }
}