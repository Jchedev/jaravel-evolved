<?php

namespace Jchedev\Laravel\Classes\Services;

class Field
{
    /**
     * @var
     */
    protected $key;

    /**
     * @var null
     */
    protected $as = null;

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
     * @return string
     */
    public function getAs()
    {
        return $this->as;
    }

    /**
     * @param array $validationRules
     * @return $this
     */
    public function validationRules(array $validationRules)
    {
        $this->validationRules = $validationRules;

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
     * @param $value
     * @return $this
     */
    public function as($value)
    {
        $this->as = $value;

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
}