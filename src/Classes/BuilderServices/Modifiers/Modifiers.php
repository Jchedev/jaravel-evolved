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
            }
        }

        return $this;
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
     * @param null $limit
     * @return $this
     */
    public function limit($limit = null)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param array $filters
     * @return $this
     */
    public function filters(array $filters = [])
    {
        $this->filters = $filters;

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
    }
}