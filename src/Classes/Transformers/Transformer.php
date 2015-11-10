<?php

namespace Jchedev\Classes\Transformers;

use Illuminate\Database\Eloquent\Model;

abstract class Transformer
{
    /**
     * @var Model
     */
    private $_model;

    /**
     * This method has to be defined in every children
     *
     * @return \Closure
     */
    abstract function   getClosure();

    /**
     * Transformer constructor.
     *
     * @param Model $model
     */
    public function     __construct(Model $model = null)
    {
        $this->_model = $model;
    }

    /**
     * Return the values associated to the model by calling the closure
     *
     * @return array
     */
    public function     getValues()
    {
        if (is_null($this->_model)) {
            return [];
        }

        $closure = $this->getClosure();

        return $closure($this->_model);
    }
}