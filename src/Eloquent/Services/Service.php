<?php

namespace Jchedev\Laravel\Eloquent\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;
use Jchedev\Laravel\Classes\Validation\Validator as CustomValidator;

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
     * @return bool
     */
    public function requiresValidation()
    {
        return $this->withValidation;
    }

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
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $data
     * @param array $options
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Model $model, array $data, array $options = [])
    {
        $validator = $this->validatorForUpdate($data);

        $validatedData = $this->validate($validator);

        return $this->onUpdate($model, $validatedData, $options);
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
     * @param array $attributes
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function onUpdate(Model $model, array $attributes, array $options = [])
    {
        $model->fill($attributes)->save($options);

        return $model;
    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validate(Validator $validator)
    {
        if ($this->requiresValidation()) {
            $validator->validate();
        }

        $data = array_only($validator->getData(), array_keys($validator->getRules()));

        if (is_a($validator, CustomValidator::class)) {
            $data = array_merge($data, $validator->getExtra());
        }

        return $data;
    }

    /**
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorForCreate(array $data = []): Validator
    {
        return $this->validator($data, $this->validationRulesForCreate());
    }

    /**
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorForUpdate(array $data = []): Validator
    {
        return $this->validator($data, $this->validationRulesForUpdate());
    }

    /**
     * @param array $data
     * @param array $rules
     * @return \Illuminate\Validation\Validator
     */
    protected function validator(array $data, array $rules): Validator
    {
        return new CustomValidator(app('translator'), $data, $rules);
    }

    /**
     * @return array
     */
    protected function validationRulesForCreate(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function validationRulesForUpdate(): array
    {
        return $this->validationRulesForCreate();
    }
}