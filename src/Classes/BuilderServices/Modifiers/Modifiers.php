<?php

namespace Jchedev\Laravel\Classes\BuilderServices\Modifiers;

use Illuminate\Database\Eloquent\Builder;

class Modifiers
{
    /**
     * @var  array
     */
    private $filters;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var array
     */
    private $withCount = [];

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
                case 'withCount':
                    $this->withCount($value);
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
    public function filters(array $filters = null)
    {
        $this->filters = is_array($filters) ? $filters : [];

        return $this;
    }

    /**
     * @param null $value
     * @return $this
     */
    public function withCount($value = null)
    {
        $this->withCount = is_array($value) ? $value : (is_null($value) ? [] : [$value]);

        return $this;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyToBuilder(Builder $builder)
    {
        if (!is_null($this->limit)) {
            $builder->take($this->limit < 0 ? 0 : $this->limit);

            $builder->skip($this->offset);
        }

        $builder->withCount($this->withCount);
    }
}