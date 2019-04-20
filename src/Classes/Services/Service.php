<?php

namespace Jchedev\Laravel\Eloquent\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Jchedev\Laravel\Exceptions\UnexpectedArgumentException;

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
    public function create(array $attributes, array $options = [])
    {
        $validator = $this->validatorForCreate($attributes);

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
    public function createMany(array $arrayOfAttributes, array $options = [])
    {
        /*$validator = $this->validatorForCreate();

        $validatedData = [];

        foreach ($arrayOfAttributes as $attributes) {
            $validator->setData($attributes);

            $validatedData[] = $this->validate($validator);
        }

        return $this->onCreateMany($validatedData, $options);*/
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
    public function update(Model $model, array $attributes, array $options = [])
    {
        $validator = $this->validatorForUpdate($model, $attributes);

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

    public function updateMany(Collection $collection, array $attributes, array $options = [])
    {

    }

    protected function onUpdateMany(Collection $collection, array $attributes, array $options = []): Collection
    {

    }

    /**
     * @param array $attributes
     * @param string $handler
     * @param array $options
     * @return false|\Illuminate\Database\Eloquent\Model|mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createOrUpdate(array $attributes, $handler = 'id', array $options = [])
    {
        $model = $this->runCreateOrUpdateHandler($handler, $attributes);

        if (is_null($model)) {
            return $this->create($attributes, $options);
        } elseif (is_a($model, get_class($this->model()))) {
            return $this->update($model, $attributes, $options);
        } else {
            $this->throwValidationException([trans('validation.exists', ['attribute' => is_string($handler) ? $handler : 'identifier'])]);
        }
    }

    /**
     * @param array $arrayOfAttributes
     * @param string $handler
     * @param array $options
     * @return mixed
     */
    public function createOrUpdateMany(array $arrayOfAttributes, $handler = 'id', array $options = [])
    {
        return DB::transaction(function () use ($arrayOfAttributes, $handler, $options) {
            $errors = [];

            $models = $this->model()->newCollection();

            foreach ($arrayOfAttributes as $key => $attributes) {
                try {
                    if (!is_array($attributes)) {
                        throw new \Exception(trans('validation.array', ['attribute' => 'key ' . $key]));
                    }

                    $model = $this->runCreateOrUpdateHandler($handler, $attributes);

                    if (is_null($model)) {
                        $models->push($this->create($attributes, $options));
                    } elseif (is_a($model, get_class($this->model()))) {
                        $models->push($this->update($model, $attributes, $options));
                    } else {
                        throw new \Exception(trans('validation.exists', ['attribute' => is_string($handler) ? $handler : 'identifier']));
                    }
                }
                catch (\Exception $exception) {
                    if (!isset($errors[$key])) {
                        $errors[$key] = [];
                    }
                    $errors[$key] += is_a($exception, ValidationException::class) ? $exception->errors() : [$exception->getMessage()];
                }
            }

            if (count($errors) != 0) {
                $this->throwValidationException($errors);
            } else {
                return $models;
            }
        });
    }

    /**
     * @param $handler
     * @param array $attributes
     * @return bool|null
     */
    protected function runCreateOrUpdateHandler($handler, array $attributes)
    {
        if (is_callable($handler)) {
            if (is_null($value = $handler($attributes))) {
                return null;
            }

            return is_a($value, get_class($this->model())) ? $value : false;
        }

        if (is_string($handler)) {
            if (is_null($id = array_get($attributes, $handler))) {
                return null;
            }

            return $this->model()->find($id) ?: false;
        }

        throw new UnexpectedArgumentException(1, ['closure', 'string']);
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
     * @param array $messages
     */
    protected function throwValidationException(array $messages)
    {
        $exception = \Illuminate\Validation\ValidationException::withMessages([]);

        $exception->validator->errors()->merge([$messages]);

        throw $exception;
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