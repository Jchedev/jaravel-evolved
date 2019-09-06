<?php

namespace Jchedev\Laravel\Classes\Services;

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
    abstract protected function fields(): array;

    /*
     * Create one or many models
     */

    /**
     * @param array $data
     * @param array $options
     * @return false|\Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Validation\ValidationException
     */
    final public function create(array $attributes, array $options = []): Model
    {
        $validator = $this->validatorForCreate($attributes);

        $validatedData = $this->validate($validator);

        $finalAttributes = $this->beforeCreating($validatedData, $options);

        $model = $this->performCreate($finalAttributes, $options);

        return $this->afterCreating($model, $options);
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return array
     */
    protected function beforeCreating(array $attributes, array $options): array
    {
        return $attributes;
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function performCreate(array $attributes, array $options): Model
    {
        $model = clone $this->model();

        $model->fill($attributes)->save($options);

        return $model;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function afterCreating(Model $model, array $options): Model
    {
        return $model;
    }

    /**
     * @param array $data
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \Illuminate\Validation\ValidationException
     */
    final public function createMany(array $arrayOfAttributes, array $options = []): Collection
    {
        $validators = [];

        foreach ($arrayOfAttributes as $attributes) {
            $validators[] = $this->validatorForCreate($attributes);
        }

        $validatedData = $this->validateMany($validators);

        $finalAttributes = $this->beforeCreatingMany($validatedData, $options);

        $collection = DB::transaction(function () use ($finalAttributes, $options) {
            return $this->performCreateMany($finalAttributes, $options);
        });

        return $this->afterCreatingMany($collection, $options);
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return array
     */
    protected function beforeCreatingMany(array $arrayOfAttributes, array $options): array
    {
        foreach ($arrayOfAttributes as $key => $attributes) {
            $arrayOfAttributes[$key] = $this->beforeCreating($attributes, $options);
        }

        return $arrayOfAttributes;
    }

    /**
     * @param array $arrayOfAttributes
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function performCreateMany(array $arrayOfAttributes, array $options): Collection
    {
        return $this->model()->createMany($arrayOfAttributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function afterCreatingMany(Collection $collection, array $options): Collection
    {
        return $collection;
    }

    /*
     * Update one model
     */

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $data
     * @param array $options
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    final public function update(Model $model, array $attributes, array $options = [])
    {
        $validator = $this->validatorForUpdate($model, $attributes);

        $validatedData = $this->validate($validator);

        $finalAttributes = $this->beforeUpdating($model, $validatedData, $options);

        $model = $this->performUpdate($model, $finalAttributes, $options);

        return $this->afterUpdating($model, $options);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $attributes
     * @param array $options
     * @return array
     */
    protected function beforeUpdating(Model $model, array $attributes, array $options): array
    {
        return $attributes;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $attributes
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function performUpdate(Model $model, array $attributes, array $options): Model
    {
        $model->fill($attributes)->save($options);

        return $model;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function afterUpdating(Model $model, array $options): Model
    {
        return $model;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $attributes
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    final public function updateMany(Collection $collection, array $attributes, array $options = [])
    {
        $validators = [];

        foreach ($collection as $model) {
            $validators[] = $this->validatorForUpdate($model, $attributes);
        }

        $validatedData = $this->validateMany($validators);

        $finalAttributes = $this->beforeUpdatingMany($collection, $validatedData, $options);

        $collection = DB::transaction(function () use ($collection, $finalAttributes, $options) {
            return $this->performUpdateMany($collection, $finalAttributes, $options);
        });

        return $this->afterUpdatingMany($collection, $options);
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return array
     */
    protected function beforeUpdatingMany(Collection $collection, array $arrayOfAttributes, array $options): array
    {
        foreach ($collection as $key => $model) {
            $arrayOfAttributes[$key] = $this->beforeUpdating($model, $arrayOfAttributes[$key], $options);
        }

        return $arrayOfAttributes;
    }

    /**
     * @param array $arrayOfAttributes
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function performUpdateMany(Collection $collection, array $arrayOfAttributes, array $options): Collection
    {
        foreach ($collection as $key => $model) {
            $collection[$key] = $this->performUpdate($model, $arrayOfAttributes[$key], $options);
        }

        return $collection;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function afterUpdatingMany(Collection $collection, array $options): Collection
    {
        return $collection;
    }

    /*
     * Delete one or many models
     */

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $options
     * @return bool|null
     * @throws \Exception
     */
    final public function delete(Model $model, array $options = [])
    {
        return $this->performDelete($model, $options);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $options
     * @return bool|null
     * @throws \Exception
     */
    protected function performDelete(Model $model, array $options)
    {
        return $model->delete();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $options
     */
    final public function deleteMany(Collection $collection, array $options = [])
    {
        return DB::transaction(function () use ($collection, $options) {
            return $this->performDeleteMany($collection, $options);
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    protected function performDeleteMany(Collection $collection, array $options)
    {
        foreach ($collection as $element) {
            $this->performDelete($element);
        }

        return true;
    }

    /**
     * @param array $attributes
     * @param string $handler
     * @param array $options
     * @return false|\Illuminate\Database\Eloquent\Model|mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createOrUpdate(array $attributes, $handler = null, array $options = [])
    {
        if (is_null($handler)) {
            $handler = $this->model()->getKeyName();
        }

        $model = $this->runCreateOrUpdateHandler($handler, $attributes);

        if (!is_null($model)) {
            return $this->update($model, $attributes, $options);
        }

        return $this->create($attributes, $options);
    }

    /**
     * @param array $arrayOfAttributes
     * @param string $handler
     * @param array $options
     * @return mixed
     */
    public function createOrUpdateMany(array $arrayOfAttributes, $handler = 'id', array $options = [])
    {
        die('to redo');
        /*return DB::transaction(function () use ($arrayOfAttributes, $handler, $options) {
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
        });*/
    }

    /**
     * Will return a model (or null) based on an handler logic.
     *
     * @param $handler
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws \Exception
     */
    protected function runCreateOrUpdateHandler($handler, array $attributes): ?Model
    {
        // Handler can be a function which should return the model found
        if (is_callable($handler)) {
            if (is_null($value = $handler($attributes))) {
                return null;
            }

            if (!is_a($value, get_class($this->model()))) {
                throw new \Exception('createOrUpdate handler returns invalid result');
            }

            return $value;
        }

        // Handler can be a string like "id" which will look at the attribute
        if (is_string($handler)) {
            if (is_null($attributeValue = array_get($attributes, $handler))) {
                return null;
            }

            return $this->model()->where($handler, '=', $attributeValue)->first();
        }

        throw new UnexpectedArgumentException(1, ['closure', 'string']);
    }

    /*
     * Validation methods
     */

    /**
     * Return the validator used during a Create
     * Can be overwritten by a child to add ->after(...) to it
     *
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    public function validatorForCreate(array $data = []): Validator
    {
        $rules = $this->validationRulesForCreate();

        return $this->validator($data, $rules);
    }

    /**
     * Return the list of validation rules to check during create
     * Can be overwritten to add rules which are specific to the creation (and not set in the fields() configuration)
     *
     * @return array
     */
    public function validationRulesForCreate(): array
    {
        $validationRules = [];

        foreach ($this->fields() as $key => $field) {
            if (is_array($field) || is_string($field)) {
                // $field can be an "regular" validation rule (array or string)
                $validationRules[$key] = (array)$field;
            } elseif (is_a($field, Field::class)) {
                // $field can be a Field object
                $validationRules[$key] = $field->getValidationRules();
            }
        }

        return $validationRules;
    }

    /**
     * Return the validator used during an Update on a model
     * Can be overwritten by a child to add ->after(...) to it
     *
     * @param array $data
     * @return \Illuminate\Validation\Validator
     */
    public function validatorForUpdate(Model $model, array $data = []): Validator
    {
        $rules = array_only($this->validationRulesForUpdate($model), array_keys($data));

        return $this->validator($data, $rules);
    }

    /**
     * Return the list of validation rules to check during update
     * Can be overwritten to add rules which are specific to the creation (and not set in the fields() configuration)
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    public function validationRulesForUpdate(Model $model): array
    {
        $validationRules = [];

        foreach ($this->fields() as $key => $field) {
            if (is_array($field) || is_string($field)) {
                // $field can be an "regular" validation rule (array or string)
                $validationRules[$key] = (array)$field;
            } elseif (is_a($field, Field::class) && $field->isEditable()) {
                // $field can be a Field object but would need to be Editable
                $validationRules[$key] = $field->getValidationRules();
            }
        }

        return $validationRules;
    }

    /**
     * Return the base validator used by validatorForCreate & validatorForUpdate
     *
     * @param array $data
     * @param array $rules
     * @return \Illuminate\Validation\Validator
     */
    protected function validator(array $data, array $rules): Validator
    {
        return \Validator::make($data, $rules);
    }

    /**
     * Execute the validation IF the validation is not disabled already
     *
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
     * @param array $validators
     * @return array
     */
    protected function validateMany(array $validators)
    {
        $errors = [];

        $validatedAttributes = [];

        foreach ($validators as $key => $validator) {
            try {
                $validatedAttributes[] = $this->validate($validator);
            }
            catch (\Exception $exception) {
                $errors[$key] = is_a($exception, ValidationException::class) ? $exception->errors() : [$exception->getMessage()];
            }
        }

        if (count($errors) != 0) {
            $this->throwValidationException($errors);
        }

        return $validatedAttributes;
    }

    /**
     * Generate and throw a validation exception based on multiple messages
     *
     * @param array $messages
     */
    protected function throwValidationException(array $messages)
    {
        $exception = \Illuminate\Validation\ValidationException::withMessages([]);

        $exception->validator->errors()->merge([$messages]);

        throw $exception;
    }
}