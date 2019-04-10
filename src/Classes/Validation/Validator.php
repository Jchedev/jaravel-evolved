<?php

namespace Jchedev\Laravel\Classes\Validation;

class Validator extends \Illuminate\Validation\Validator
{
    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @return bool
     */
    public function validateConvert($attribute, $value, $parameters)
    {
        if (is_callable($closure = array_get($parameters, 0))) {
            if (($value = $closure($value)) === false) {
                return false;
            }

            $this->data[$attribute] = $value;
        }

        return true;
    }

    /**
     * @param $message
     * @param $attribute
     * @param $rule
     * @param $parameters
     * @return mixed
     */
    protected function replaceConvert($message, $attribute, $rule, $parameters)
    {
        return 'Invalid ' . $attribute;
    }
}