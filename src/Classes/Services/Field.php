<?php

namespace Jchedev\Laravel\Classes\Services;

class Field
{
    /**
     * @var
     */
    protected $key;

    /**
     * @var bool
     */
    protected $editable = true;

    /**
     * @var array
     */
    protected $validationRules = [];

    /**
     * Field constructor.
     *
     * @param string $key
     * @param array $validationRules
     */
    public function __construct(string $key, array $validationRules = [])
    {
        $this->key = $key;

        $this->validationRules($validationRules);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param array $validationRules
     * @return $this
     */
    public function validationRules(array $validationRules)
    {
        $this->validationRules = [];

        foreach ($validationRules as $key => $value) {
            $this->validationRule($key, $value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getValidationRules()
    {
        return $this->validationRules;
    }

    /**
     * @param bool $boolean
     * @return $this
     */
    public function editable(bool $boolean = true)
    {
        $this->editable = $boolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEditable()
    {
        return $this->editable;
    }

    /**
     * @param string $key
     * @param array $validationRules
     * @return \Jchedev\Laravel\Classes\Services\Field
     */
    static function make(string $key, array $validationRules = [])
    {
        return new self($key, $validationRules);
    }

    /**
     * @param $key
     * @param $value
     */
    protected function validationRule($key, $value)
    {
        if (is_string($key)) {
            if (is_string($value)) {
                $value = $key . ':' . $value;
            } elseif (is_array($value)) {
                array_unshift($value, $key);
            }

        } else {
            if (is_string($value)) {
                $key = array_first(explode(':', $value));
            } elseif (is_array($value)) {
                $key = array_first($value);
            }
        }

        $this->validationRules[$key] = $value;
    }
}