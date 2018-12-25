<?php

namespace Jchedev\Laravel\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ModelExists implements Rule
{
    protected $check_on;

    protected $attribute;

    /**
     * ModelExists constructor.
     *
     * @param $check_on
     * @param null $attribute
     */
    public function __construct($check_on, $attribute = null)
    {
        $this->check_on = $check_on;

        $this->attribute = $attribute;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $model = null;

        if (is_a($this->check_on, Model::class, true)) {
            $model = $this->checkOnModel(new $this->check_on, $value, $this->attribute);
        }

        return !is_null($model);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param $value
     * @param null $on_attribute
     * @return \Illuminate\Database\Eloquent\Model|\Jchedev\Laravel\Rules\ModelExists|null|object
     */
    protected function checkOnModel(Model $model, $value, $on_attribute = null)
    {
        return $this->checkOnBuilder($model->newQuery(), $value, $on_attribute);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param $value
     * @param null $on_attribute
     * @return \Illuminate\Database\Eloquent\Model|null|object|static
     */
    protected function checkOnBuilder(Builder $builder, $value, $on_attribute = null)
    {
        $on_attribute = $on_attribute ?: $builder->getModel()->getQualifiedKeyName();

        $object = $builder->where($on_attribute, '=', $value)->first();

        return $object;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
