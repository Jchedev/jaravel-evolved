<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class Model implements ValidationRule, ValidatorAwareRule
{
    protected Validator $validator;

    protected Closure|null $scope = null;

    protected array $relations = [];

    /**
     * @param $class
     * @param $where
     */
    public function __construct(protected $class, protected $where = null)
    {
    }

    /**
     * @param \Closure|string $value
     * @return $this
     */
    public function scope(Closure|string $value)
    {
        if (is_string($value)) {
            $this->scope = function ($builder) use ($value) {
                $builder->$value();
            };
        } else {
            $this->scope = $value;
        }

        return $this;
    }

    /**
     * @param string|array $relationName
     * @return $this
     */
    public function relatedTo(string|array $relationName)
    {
        if (is_string($relationName)) {
            $relationName = [$relationName];
        }

        $this->relations += $relationName;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param \Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $className = $this->class;

        $data = $this->validator->getData();

        if ($value instanceof \App\Models\Model) {
            // The value is already an instanciated model. Just need to compare class name
            if ($value instanceof $className === false) {
                $fail('validation.model')->translate();
            }
            //todo: should check scope/relatedTo here too

        } else {
            // We use a Builder to see if a model exists with this "key" + optional scopes/relation
            $builder = $className::query();

            $builder->where($this->where ?: $className::routeKeyname(), $value);

            if (!is_null($this->scope)) {
                call_user_func($this->scope, $builder);
            }

            if (count($this->relations)) {
                foreach ($this->relations as $relationName => $relatedAttribute) {
                    if (is_integer($relationName)) {
                        $relationName = $relatedAttribute;
                    }

                    if (($relatedModel = Arr::get($data, $relatedAttribute)) instanceof \App\Models\Model === false) {
                        $fail('validation.model')->translate();

                        return;
                    }

                    $builder->whereHas($relationName, fn($q) => $q->whereRouteKey($relatedModel->getRouteKey()));
                }
            }

            if (is_null($model = $builder->first())) {
                $fail('validation.model')->translate();
            } else {
                Arr::set($data, $attribute, $model);

                $this->validator->setData($data);
            }
        }
    }

    /**
     * @param $validator
     * @return void
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }
}
