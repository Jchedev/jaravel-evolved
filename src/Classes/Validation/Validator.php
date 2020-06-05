<?php

namespace Jchedev\Laravel\Classes\Validation;

use Illuminate\Database\Eloquent\Model;

class Validator extends \Illuminate\Validation\Validator
{
    /**
     * @var array
     */
    protected $variablesToReturn = [];

    /**
     * Validator constructor.
     *
     * @param \Illuminate\Contracts\Translation\Translator $translator
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     */
    public function __construct(\Illuminate\Contracts\Translation\Translator $translator, array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        parent::__construct($translator, $data, $rules, $messages, $customAttributes);

        $this->customMessages['array_or_json'] = ':attribute is not an array';

        $this->customMessages['is'] = ':attribute is not the right type of object';
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function updateData($key, $value)
    {
        // This is the only way to return the new value through ->validated()
        if (array_has($this->data, $key) === false) {
            $this->variablesToReturn[] = $key;
        }

        // Add/Replace the value from the validator's data object
        array_set($this->data, $key, $value);

        return $this;
    }

    /*
     * Modified Methods
     */

    /**
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validated()
    {
        $validatedData = parent::validated();

        foreach ($this->variablesToReturn as $variable) {
            if (!array_has($validatedData, $variable)) {
                array_set($validatedData, $variable, data_get($this->getData(), $variable));
            }
        }

        return $validatedData;
    }

    /**
     * @param array $rules
     */
    public function addRules($rules)
    {
        foreach ($rules as $key => $data) {
            $rules[$key] = self::formatRules($data);
        }

        return parent::addRules($rules);
    }

    /*
     * New Methods / rules
     */

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @return bool
     */
    public function validateTransform($attribute, $value, $parameters)
    {
        $transformer = array_shift($parameters);

        $as = array_shift($parameters);

        try {
            if (is_callable($transformer)) {
                $newValue = $this->transformThroughClosure($transformer, $attribute, $value);
            } elseif (is_subclass_of($transformer, Model::class)) {
                $newValue = $this->transformModel($transformer, $value, array_shift($parameters));
            } else {
                throw new \Exception('Undefined transformation for ' . $attribute);
            }
        }
        catch (\Exception $e) {
            $this->setCustomMessages([$attribute . '.transform' => $e->getMessage()]);

            return false;
        }

        $this->updateData($as ?: $attribute, $newValue);

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @return bool
     */
    public function validateIs($attribute, $value, $parameters)
    {
        $class = array_shift($parameters);

        if (!is_null($value) && !$value instanceof $class) {
            return false;
        }

        return true;
    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     * @return bool
     */
    public function validateArrayOrJson($attribute, $value, $parameters)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            return false;
        }

        $this->updateData($attribute, $value);

        return true;
    }

    /**
     * @param callable $closure
     * @param $value
     * @param $validator
     * @return mixed
     */
    protected function transformThroughClosure(callable $closure, $attribute, $value)
    {
        return $closure($attribute, $value, function ($message) {
            throw new \Exception($message);
        }, $this);
    }

    /**
     * @param $class
     * @param $value
     * @param null $key
     * @return null
     * @throws \Exception
     */
    protected function transformModel($class, $value, $key = null)
    {
        if (is_array($value)) {
            throw new \Exception('Array not authorized as value');
        }

        if (!is_null($value)) {
            if (!is_object($value)) {
                $value = $class::query()->where($key ?: $class::keyName(), '=', $value)->first();
            }

            if (!is_a($value, $class)) {
                throw new \Exception('Invalid ' . get_class_basename($class));
            }
        }

        return $value;
    }

    /**
     * @param $rules
     * @return array
     */
    static function formatRules($rules)
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        if (!is_array($rules)) {
            return $rules;
        }

        $newRules = [];

        foreach ($rules as $key => $value) {
            $ruleName = null;

            if (is_string($key)) {
                // When the key is the rule name : {"required" => true} , { "min" => 5} , {"between" => [5, 10]}, {"between" => "5,10" }
                $ruleName = $key;

                if (is_string($value)) {
                    $value = explode(',', $value);
                }
            } else {
                if (is_array($value)) {
                    // When the $value is an array, the rule should be the first parameter
                    $ruleName = array_shift($value);
                } elseif (is_string($value)) {
                    // When the $value is the concatenated rule + params : "required", "min:5", "between:5,10"
                    $exploded = explode(':', $value);

                    $ruleName = $exploded[0];

                    $value = isset($exploded[1]) ? explode(',', $exploded[1]) : null;
                }
            }

            // We want to try to save the rule name as the associated key for easy access
            if (is_null($ruleName)) {
                $newRules[$key] = $value;
            } else {
                // We want to re-create an array rule => [rule, param1, param2, ...]
                if (is_array($value)) {
                    $newRules[$ruleName] = array_merge([$ruleName], $value);
                } elseif ((is_null($value) || is_string($value)) && $value !== $ruleName) {
                    $newRules[$ruleName] = array_merge([$ruleName], !is_null($value) ? [$value] : []);
                } else {
                    $newRules[$ruleName] = $value;
                }
            }
        }

        return $newRules;
    }
}