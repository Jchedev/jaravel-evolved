<?php

namespace Jchedev\Laravel\Exceptions;

/**
 * Class UnexpectedClassException
 *
 * @package Jchedev\Laravel\Exceptions
 */
class UnexpectedClassException extends \UnexpectedValueException
{
    public function __construct($object, $classes = null, $message = "", $code = 0, $previous = null)
    {
        if ($message == '') {
            $message = 'Invalid object: ' . (is_null($object) ? 'null' : get_class($object)) . ' given.';

            if (is_string($classes)) {
                $message .= ' ' . $classes . ' expected.';
            }
            if (is_array($classes)) {
                $allowed = [];
                foreach ($classes as $class) {
                    $allowed[] = is_string($class) ? $class : (is_array($class) ? 'Array' : null);
                }

                $message .= ' ' . implode(' or ', $allowed) . ' expected.';
            }
        }

        parent::__construct($message, $code, $previous);
    }
}