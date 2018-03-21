<?php

namespace Jchedev\Laravel\Classes\BuilderServices;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers;

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
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        $validator = $this->validatorForCreate($data);

        $validator->validate();

        $validated_data = array_only($validator->valid(), array_keys($this->validationRulesForCreate()));

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