<?php

namespace Jchedev\Laravel\Classes\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

class TransformRule
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
        $transformer = array_shift($parameters);

        try {
            if (is_callable($transformer)) {
                $newValue = $this->validateThroughClosure($transformer, $attribute, $value, $validator);
            } elseif (is_subclass_of($transformer, Model::class)) {
                $newValue = $this->validateModel($transformer, $value, array_shift($parameters));
            } else {
                throw new \Exception('Undefined transformation for ' . $attribute);
            }
        }
        catch (\Exception $e) {
            $this->setErrorMessage($validator, $attribute, $e->getMessage());

            return false;
        }

        $this->updateValidatorData($validator, [$attribute => $newValue]);

        return true;
    }

    /**
     * @param callable $closure
     * @param $value
     * @param $validator
     * @return mixed
     */
    protected function validateThroughClosure(callable $closure, $attribute, $value, Validator $validator)
    {
        return $closure($attribute, $value, function ($message) {
            throw new \Exception($message);
        }, $validator);
    }

    /**
     * @param $class
     * @param $value
     * @param null $key
     * @return null
     * @throws \Exception
     */
    protected function validateModel($class, $value, $key = null)
    {
        if (is_array($value)) {
            throw new \Exception('Array not authorized as value');
        }

        if (is_null($value)) {
            return null;
        }

        if (!is_object($value)) {
            $value = $class::query()->where($key ?: $class::keyName(), '=', $value)->first();
        }

        if (!is_a($value, $class)) {
            throw new \Exception('Invalid ' . get_class_basename($class));
        }

        return $value;
    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @param array $newData
     */
    protected function updateValidatorData(Validator $validator, array $newData)
    {
        $data = $validator->getData();

        foreach ($newData as $key => $value) {
            $data[$key] = $value;
        }

        $validator->setData($data);
    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @param $attribute
     * @param $errorMessage
     */
    protected function setErrorMessage(Validator $validator, $attribute, $errorMessage)
    {
        foreach ($validator->extensions as $key => $extension) {
            if (is_string($extension) && $extension == get_class($this)) {
                $validator->setCustomMessages([
                    $attribute . '.' . $key => $errorMessage
                ]);
                break;
            }
        }
    }
}