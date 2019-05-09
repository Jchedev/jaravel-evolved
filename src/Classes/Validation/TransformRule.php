<?php

namespace Jchedev\Laravel\Classes\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

class TransformRule
{
    protected $errorMessage;

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

        if (is_callable($transformer)) {
            $newValue = $this->validateThroughClosure($transformer, $value, $validator);
        } elseif (is_subclass_of($transformer, Model::class)) {
            $newValue = $this->validateModel($transformer, $value, $validator);
        } else {
            throw new \Exception('Undefined transformation for ' . $attribute);
        }

        if (!is_null($this->errorMessage)) {
            $this->setErrorMessage($validator, $attribute);

            return false;
        } else {
            $this->updateValidatorData($validator, [$attribute => $newValue]);

            return true;
        }
    }

    /**
     * @param callable $closure
     * @param $value
     * @param $validator
     * @return mixed
     */
    protected function validateThroughClosure(callable $closure, $value, $validator)
    {
        try {
            return $closure($value, function ($message) {
                throw new \Exception($message);
            }, $validator);
        }
        catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * @param $class
     * @param $value
     * @param $validator
     * @return null
     */
    protected function validateModel($class, $value, $validator)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_object($value)) {
            if (is_a($value, $class)) {
                return $value;
            } else {
                $this->errorMessage = 'Invalid model';

                return false;
            }
        }

        if (is_array($value)) {
            $this->errorMessage = 'Array not authorized as value';

            return false;
        }

        if (!is_null($model = $class::find($value))) {
            return $model;
        }

        $this->errorMessage = 'Invalid ' . get_class_basename($class);

        return false;
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
     */
    protected function setErrorMessage(Validator $validator, $attribute)
    {
        $translationKey = 'validation.' . $this->errorMessage;

        $errorMessage = $validator->getTranslator()->trans($translationKey);

        if ($errorMessage == $translationKey) {
            $errorMessage = $this->errorMessage;
        }

        if (($extensionName = $this->findExtensionName($validator)) !== false) {
            $validator->setCustomMessages([$attribute . '.' . $extensionName => $errorMessage]);
        }
    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @return bool|int|string
     */
    protected function findExtensionName(Validator $validator)
    {
        foreach ($validator->extensions as $name => $extension) {
            if (is_string($extension) && $extension == get_class($this)) {
                return $name;
            }
        }

        return false;
    }
}