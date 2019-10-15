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

    /**
     * Placeholder for adding logic BEFORE the create is executed
     *
     * @param array $attributes
     * @return array
     */
    protected function beforeAny(array $attributes): array
    {
        return $attributes;
    }

    /*
     * Create one or many models
     */

    /**
     * Create one model
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Validation\ValidationException
     */
    final public function create(array $attributes): Model
    {
        $validator = $this->validatorForCreate($attributes);

        $validatedData = $this->validate($validator);

        return DB::transaction(function () use ($validatedData) {
            $validatedData = $this->beforeAny($validatedData);

            $finalAttributes = $this->beforeCreating($validatedData);

            $model = $this->performCreate($finalAttributes);

            $model = $this->afterCreating($model);

            $this->setRelations($model, array_merge($validatedData, $finalAttributes));

            return $model;
        });
    }

    /**
     * Placeholder for adding logic BEFORE the create is executed
     *
     * @param array $attributes
     * @return array
     */
    protected function beforeCreating(array $attributes): array
    {
        return $attributes;
    }

    /**
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function performCreate(array $attributes): Model
    {
        return $this->model()->create($attributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function afterCreating(Model $model): Model
    {
        return $model;
    }

    /**
     * @param array $arrayOfAttributes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    final public function createMany(array $arrayOfAttributes): Collection
    {
        $validators = [];

        foreach ($arrayOfAttributes as $attributes) {
            $validators[] = $this->validatorForCreate($attributes);
        }

        $validatedData = $this->validateMany($validators);

        return DB::transaction(function () use ($validatedData) {
            foreach ($validatedData as $key => $data) {
                $validatedData[$key] = $this->beforeAny($data);
            }

            $finalAttributes = $this->beforeCreatingMany($validatedData);

            $collection = $this->performCreateMany($finalAttributes);

            $collection = $this->afterCreatingMany($collection);

            foreach ($collection as $key => $model) {
                $this->setRelations($model, array_merge($validatedData[$key], $finalAttributes[$key]));
            }

            return $collection;
        });
    }

    /**
     * @param array $arrayOfAttributes
     * @return array
     */
    protected function beforeCreatingMany(array $arrayOfAttributes): array
    {
        foreach ($arrayOfAttributes as $key => $attributes) {
            $arrayOfAttributes[$key] = $this->beforeCreating($attributes);
        }

        return $arrayOfAttributes;
    }

    /**
     * @param array $arrayOfAttributes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function performCreateMany(array $arrayOfAttributes): Collection
    {
        return $this->model()->createMany($arrayOfAttributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function afterCreatingMany(Collection $collection): Collection
    {
        return $collection;
    }

    /*
     * Update one model
     */

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Validation\ValidationException
     */
    final public function update(Model $model, array $attributes)
    {
        $validator = $this->validatorForUpdate($model, $attributes);

        $validatedData = $this->validate($validator);

        return DB::transaction(function () use ($model, $validatedData) {
            $validatedData = $this->beforeAny($validatedData);

            $finalAttributes = $this->beforeUpdating($model, $validatedData);

            $model = $this->performUpdate($model, $finalAttributes);

            $model = $this->afterUpdating($model);

            $this->setRelations($model, array_merge($validatedData, $finalAttributes));

            return $model;
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $attributes
     * @return array
     */
    protected function beforeUpdating(Model $model, array $attributes): array
    {
        return $attributes;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function performUpdate(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function afterUpdating(Model $model): Model
    {
        return $model;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    final public function updateMany(Collection $collection, array $attributes)
    {
        $validators = [];

        foreach ($collection as $model) {
            $validators[] = $this->validatorForUpdate($model, $attributes);
        }

        $validatedData = $this->validateMany($validators);

        return DB::transaction(function () use ($collection, $validatedData) {
            foreach ($validatedData as $key => $data) {
                $validatedData[$key] = $this->beforeAny($data);
            }

            $finalAttributes = $this->beforeUpdatingMany($collection, $validatedData);

            $collection = $this->performUpdateMany($collection, $finalAttributes);

            $collection = $this->afterUpdatingMany($collection);

            foreach ($collection as $key => $model) {
                $this->setRelations($model, array_merge($validatedData[$key], $finalAttributes[$key]));
            }

            return $collection;
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $arrayOfAttributes
     * @return array
     */
    protected function beforeUpdatingMany(Collection $collection, array $arrayOfAttributes): array
    {
        foreach ($collection as $key => $model) {
            $arrayOfAttributes[$key] = $this->beforeUpdating($model, $arrayOfAttributes[$key]);
        }

        return $arrayOfAttributes;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $arrayOfAttributes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function performUpdateMany(Collection $collection, array $arrayOfAttributes): Collection
    {
        foreach ($collection as $key => $model) {
            $collection[$key] = $this->performUpdate($model, $arrayOfAttributes[$key]);
        }

        return $collection;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function afterUpdatingMany(Collection $collection): Collection
    {
        return $collection;
    }

    /*
     * Delete one or many models
     */

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @throws \Exception
     */
    final public function delete(Model $model)
    {
        DB::transaction(function () use ($model) {
            $this->beforeDeleting($model);

            $this->performDelete($model);

            $this->afterDeleting($model);
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    protected function beforeDeleting(Model $model)
    {
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool|null
     * @throws \Exception
     */
    protected function performDelete(Model $model)
    {
        return $model->delete();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    protected function afterDeleting(Model $model)
    {
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     */
    final public function deleteMany(Collection $collection)
    {
        DB::transaction(function () use ($collection) {
            $this->beforeDeletingMany($collection);

            DB::transaction(function () use ($collection) {
                $this->performDeleteMany($collection);
            });

            $this->afterDeletingMany($collection);
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     */
    protected function beforeDeletingMany(Collection $collection)
    {
        foreach ($collection as $model) {
            $this->beforeDeleting($model);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @throws \Exception
     */
    protected function performDeleteMany(Collection $collection)
    {
        foreach ($collection as $element) {
            $this->performDelete($element);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $collection
     */
    protected function afterDeletingMany(Collection $collection)
    {
        foreach ($collection as $model) {
            $this->afterDeleting($model);
        }
    }

    /**
     * @param array $attributes
     * @param null $handler
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function createOrUpdate(array $attributes, $handler = null)
    {
        if (is_null($handler)) {
            $handler = $this->model()->getKeyName();
        }

        $model = $this->runCreateOrUpdateHandler($handler, $attributes);

        if (!is_null($model)) {
            return $this->update($model, $attributes);
        }

        return $this->create($attributes);
    }

    /**
     * @param array $arrayOfAttributes
     * @param null $handler
     */
    public function createOrUpdateMany(array $arrayOfAttributes, $handler = null)
    {
        if (is_null($handler)) {
            $handler = $this->model()->getKeyName();
        }

        return DB::transaction(function () use ($arrayOfAttributes, $handler) {
            $collection = $this->model()->newCollection();

            $elementsToAdd = [];

            foreach ($arrayOfAttributes as $attributes) {
                $model = $this->runCreateOrUpdateHandler($handler, $attributes);

                if (is_null($model)) {
                    $elementsToAdd[] = $attributes;
                } else {
                    $model = $this->update($model, $attributes);

                    $collection->add($model);
                }
            }

            if (count($elementsToAdd)) {
                $newModels = $this->createMany($elementsToAdd);

                $collection = $collection->merge($newModels);
            }

            return $collection;
        });
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

    /*
     * Other helpers & small methods
     */

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $attributes
     */
    protected function setRelations(Model $model, array $attributes)
    {
        foreach ($this->fields() as $key => $field) {
            if (!is_a($field, Field::class) || empty($attributes[$key])) {
                continue;
            }

            foreach ($field->getRelations() as $relationMethodName) {
                if (method_exists($model, $relationMethodName)) {
                    $model->setRelation($relationMethodName, $attributes[$key]);
                }
            }
        }
    }
}