<?php

namespace Jchedev\Laravel\Classes\Validation;

use Illuminate\Database\Eloquent\Model;

class Validator extends \Illuminate\Validation\Validator
{
    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @return bool
     */
    public function validateTransform($attribute, $value, $parameters, $validator)
    {
        $transformer = array_shift($parameters);

        if (is_callable($transformer)) {
            $newValue = $transformer($value, $parameters, $validator);
        } elseif (is_subclass_of($transformer, Model::class)) {
            if (is_null($value)) {
                $newValue = null;
            } elseif (is_object($value)) {
                $newValue = is_a($value, $transformer) ? $value : false;
            } elseif (is_array($value)) {
                $newValue = false;
            } else {
                $key = array_get($parameters, 0, (new $transformer)->getKeyName());

                $newValue = $transformer::where($key, '=', $value)->first() ?: false;
            }
        } else {
            $newValue = false;
        }

        if ($newValue === false) {
            return false;
        }

        $validator->data[$attribute] = $newValue;

        return true;
    }

    /**
     * @param $message
     * @param $attribute
     * @param $rule
     * @param $parameters
     * @return mixed
     */
    protected function replaceTransform($message, $attribute, $rule, $parameters)
    {
        return 'Invalid ' . $attribute;
    }
}