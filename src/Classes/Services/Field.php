<?php

namespace Jchedev\Laravel\Classes\Services;

class Field
{
    /**
     * @var bool
     */
    protected $editable = true;

    /**
     * @var array
     */
    protected $validationRules = [];

    /**
     * @var array
     */
    protected $relations = [];

    /**
     * Field constructor.
     *
     * @param array $validationRules
     * @param null $relations
     */
    public function __construct(array $validationRules = [], $relations = null)
    {
        $this->validationRules($validationRules);

        if (!is_null($relations)) {
            $this->relations($relations);
        }
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
     * @param $relations
     * @return $this
     */
    public function relations($relations)
    {
        $this->relations += (array)$relations;

        return $this;
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
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
     * @param array $validationRules
     * @param null $relations
     * @return \App\Classes\Field
     */
    static function make(array $validationRules = [], $relations = null)
    {
        return new self($validationRules, $relations);
    }
}