<?php

namespace Jchedev\Laravel\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsObject implements Rule
{
    protected $class;

    /**
     * IsObject constructor.
     *
     * @param null $class
     */
    public function __construct($class = null)
    {
        $this->class = $class;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_object($value) === false) {
            return false;
        }

        if (!is_null($this->class) && is_a($value, $this->class, true) === false) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
