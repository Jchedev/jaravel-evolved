<?php

namespace Jchedev\Laravel\Classes\BuilderServices;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers;
use Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator;

abstract class BuilderService
{
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
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        $validated_data = $this->validatorForCreate($data)->validate();

        return $this->onCreate($validated_data);
    }

    /**
     * @param array $data
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    protected function onCreate(array $data)
    {
        return $this->builder()->create($data);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function validatorForCreate(array $data)
    {
        return Validator::make($data, $this->validationRulesForCreate());
    }

    /**
     * @return array
     */
    public function validationRulesForCreate()
    {
        return [];
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
     * @param int $per_page
     * @param array $columns
     * @return \Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator
     */
    public function paginate(Modifiers $modifiers = null, $per_page = 15, $columns = ['*'])
    {
        $modifiers = $this->preparePaginateModifiers($modifiers, $per_page);

        $builder = $this->modifiedBuilder($modifiers);

        $total = $builder->toBase()->getCountForPagination();

        $items = ($total != 0 ? $builder->get($columns) : $builder->getModel()->newCollection());

        return new ByOffsetLengthAwarePaginator($items, $total, $modifiers->getLimit(), $modifiers->getOffset());
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @param int $per_page
     * @return \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers
     */
    private function preparePaginateModifiers(Modifiers $modifiers = null, $per_page = 15)
    {
        $modifiers = $modifiers ?: new Modifiers();

        $limit = !is_null($limit = $modifiers->getLimit()) ? (int)$limit : $per_page;

        $offset = !is_null($offset = $modifiers->getOffset()) ? (int)$offset : 0;

        return $modifiers->limit($limit)->offset($offset);
    }

    /**
     * @param $id
     * @param null $key
     * @param \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null $modifiers
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function find($id, $key = null, Modifiers $modifiers = null, $columns = ['*'])
    {
        $builder = $this->modifiedBuilder($modifiers);

        $key = $key ?: $builder->getModel()->getKeyName();

        $builder->where($key, '=', $id);

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
}