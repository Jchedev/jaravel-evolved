<?php

namespace Jchedev\Laravel\Exceptions;

class UnexpectedArgumentException extends \UnexpectedValueException
{
    /**
     * UnexpectedArgumentException constructor.
     *
     * @param $argument
     * @param array $allowedTypes
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($argument, array $allowedTypes = [], int $code = 0, \Throwable $previous = null)
    {
        $backtrace = array_get(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2), 1);

        if (!is_null($backtrace)) {
            $parameter = $this->getReflectionParameter($backtrace['class'], $backtrace['function'], $argument);

            if (!is_null($parameter)) {
                $message = 'Argument ' . ($parameter->getPosition() + 1) . ' passed to ' . $backtrace['class'] . '::' . $backtrace['function'] . '()';

                $message .= ' must be of the type ' . implode(' or ', $allowedTypes) . ',';

                $message .= ' ' . get_variable_type(array_get($backtrace['args'], $parameter->getPosition())) . ' given.';
            }
        }

        if (empty($message)) {
            $message = 'Unexpected value for argument ' . (is_string($argument) ? '$' . $argument : $argument);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param $class
     * @param $function
     * @param $argument
     * @return \ReflectionParameter|null
     */
    protected function getReflectionParameter($class, $function, $argument)
    {
        try {
            return new \ReflectionParameter([$class, $function], is_integer($argument) ? ($argument - 1) : $argument);
        }
        catch (\ReflectionException $exception) {
            return null;
        }
    }
}