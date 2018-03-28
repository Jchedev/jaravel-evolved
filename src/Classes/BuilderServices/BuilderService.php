<?php

namespace Jchedev\Laravel\Classes\BuilderServices;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers;
use Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator;

abstract class BuilderService
{
    /**
     * @var bool
     */
    protected $with_validation = true;

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
    protected function modifyBuilder(Builder $builder, Modifiers $modifiers = null)
    {
        if (!is_null($modifiers)) {
            $modifiers->applyToBuilder($builder);
        }

        return $builder;
    }

    /**
     * @param array $data
     * @return array|\Illuminate\Database\Eloquent\Collection
     * @throws \Exception
     */
    public function createManyWithoutValidation(array $data)
    {
        $this->withoutValidation();

        try {
            $result = $this->createMany($data);
        }
        catch (\Exception $e) {
        }

        $this->withValidation();

        if (isset($e)) {
            throw $e;
        }

        return $result;
    }

    /**
     * @param array $data
     * @param bool $skip_errors
     * @return array|\Illuminate\Database\Eloquent\Collection
     * @throws \Exception
     */
    public function createMany(array $data, $skip_errors = false)
    {
        $validator = $this->validatorForCreate();

        $validated_data = [];

        foreach ($data as $element_data) {
            try {
                $validator->setData($element_data);

                $validated_data[] = $this->validate($validator);
            }
            catch (\Exception $e) {
                if ($skip_errors === false) {
                    throw $e;
                }
            }
        }

        return $this->onCreateMany($validated_data);
    }

    /**
     * @param array $data
     * @return array|\Illuminate\Database\Eloquent\Collection
     */
    protected function onCreateMany(array $data)
    {
        $return = $this->builder()->getModel()->newCollection();

        foreach ($data as $element_data) {
            $return->push($this->onCreate($element_data));
        }

        return $return;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        $validator = $this->validatorForCreate($data);

        $validated_data = $this->validate($validator);

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
    public function validatorForCreate(array $data = [])
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

    /**
     * @param bool $bool
     * @return $this
     */
    public function withValidation($bool = true)
    {
        $this->with_validation = $bool;

        return $this;
    }

    /**
     * @return \Jchedev\Laravel\Classes\BuilderServices\BuilderService
     */
    public function withoutValidation()
    {
        return $this->withValidation(false);
    }

    /**
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return array
     */
    protected function validate(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->with_validation === true) {
            $validator->validate();
        }

        return array_only($validator->getData(), array_keys($validator->getRules()));
    }
}