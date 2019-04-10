<?php

namespace Jchedev\Laravel\Classes\Validation;

use Illuminate\Validation\ClosureValidationRule;

class Validator extends \Illuminate\Validation\Validator
{
    protected $extra = [];

    /**
     * @param string $attribute
     * @param mixed $value
     * @param \Illuminate\Contracts\Validation\Rule $rule
     */
    protected function validateUsingCustomRule($attribute, $value, $rule)
    {
        if (is_a($rule, ClosureValidationRule::class)) {
            $rule = new Validation\ClosureValidationRule($rule->callback, $this);
        }

        parent::validateUsingCustomRule($attribute, $value, $rule);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setExtra($key, $value)
    {
        $this->extra[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }
}