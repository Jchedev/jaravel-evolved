<?php

namespace Jchedev\Laravel\Classes\Validation;

use Illuminate\Validation\Validator;

class ClosureValidationRule extends \Illuminate\Validation\ClosureValidationRule
{
    protected $validator;

    /**
     * ClosureValidationRule constructor.
     *
     * @param \Closure $callback
     * @param \Illuminate\Validation\Validator|null $validator
     */
    public function __construct(\Closure $callback, Validator $validator = null)
    {
        $this->validator = $validator;

        parent::__construct($callback);
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->failed = false;

        $this->callback->__invoke($attribute, $value, function ($message) {
            $this->failed = true;

            $this->message = $message;
        }, $this->validator);

        return !$this->failed;
    }
}