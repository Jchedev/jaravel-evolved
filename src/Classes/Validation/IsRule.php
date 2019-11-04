<?php

namespace Jchedev\Laravel\Classes\Validation;

use Illuminate\Validation\Validator;

class IsRule
{
    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @param \Illuminate\Validation\Validator $validator
     * @return bool
     * @throws \Exception
     */
    public function validate($attribute, $value, $parameters, Validator $validator)
    {
        $class = array_shift($parameters);

        if (!$value instanceof $class) {
            $this->setErrorMessage($validator, $attribute);

            return false;
        }

        return true;
    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @param $attribute
     */
    protected function setErrorMessage(Validator $validator, $attribute)
    {
        foreach ($validator->extensions as $key => $extension) {
            if (is_string($extension) && $extension == get_class($this)) {
                $validator->setCustomMessages([
                    $attribute . '.' . $key => $attribute . ' type is invalid'
                ]);
                break;
            }
        }
    }
}