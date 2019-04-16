<?php

namespace Jchedev\Laravel\Eloquent\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

abstract class Service
{
    /**
     * @var bool
     */
    protected $withValidation = true;

    /**
     * @return mixed
     */
    abstract protected function model(): Model;

    /**
     * @return array
     */
    abstract protected function validationRules(): array;

    /*
     * Create (one or many), Update, Delete
     */

    /**
     * @param array $data
     * @param array $options
     * @return false|\Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(array $data, array $options = [])
    {
        $validator = $this->validatorForCreate($data);

        $validatedData = $this->validate($validator);

        return $this->onCreate($validatedData, $options);
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function onCreate(array $attributes, array $options = []): Model
    {
        $model = clone $this->model();

        $model->fill($attributes)->save($options);

        return $model;
    }

    /**
     * @param array $data
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createMany(array $data, array $options = [])
    {
        $validator = $this->validatorForCreate();

        $validatedData = [];

        foreach ($data as $modelData) {
            $validator->setData($modelData);

            $validatedData[] = $this->validate($validator);
        }

        return $this->onCreateMany($validatedData, $options);
    }

    /**
     * @param array $arrayOfAttributes
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function onCreateMany(array $arrayOfAttributes, array $options = []): Collection
    {
        $model = clone $this->model();

        $collection = $model->newCollection([]);

        foreach ($arrayOfAttributes as $attributes) {
            $collection->push($this->onCreate($attributes, $options));
        }

        return $collection;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $data
     * @param array $options
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Model $model, array $data, array $options = [])
    {
        $validator = $this->validatorForUpdate($model, $data);

        $validatedData = $this->validate($validator);

        return $this->onUpdate($model, $validatedData, $options);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $attributes
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function onUpdate(Model $model, array $attributes, array $options = []): Model
    {
        $model->fill($attributes)->save($options);

        return $model;
    }

    /*
     * Validation
     */

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validate(Validator $validator)
    {
        if ($this->withValidation) {
            $validator->validate();
        }

        return array_only($validator->getData(), array_keys($validator->getRules()));
    }

    /**
     * @param array $data
     * @param array $rules
     * @return \Illuminate\Validation\Validator
     */
    protected function validator(array $data, array $rules): Validator
    {
        return \Validator::make($data, $rules);
    }

    /**
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorForCreate(array $data = []): Validator
    {
        $rules = $this->validationRulesForCreate();

        return $this->validator($data, $rules);
    }

    /**
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorForUpdate(Model $model, array $data = []): Validator
    {
        $rules = array_only($this->validationRulesForUpdate($model), array_keys($data));

        return $this->validator($data, $rules);
    }

    /**
     * @return array
     */
    protected function validationRulesForCreate(): array
    {
        return $this->validationRules();
    }

    /**
     * @return array
     */
    protected function validationRulesForUpdate(Model $model): array
    {
        return $this->validationRules();
    }
}