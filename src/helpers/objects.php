<?php

/**
 * Get the full class name of an object and return only the base (the last part)
 *
 * @param $object
 * @return string
 */
function get_basename_class($object)
{
    $class_name = is_string($object) ? $object : get_class($object);

    if ($pos = strrpos($class_name, '\\')) {
        return substr($class_name, $pos + 1);
    }

    return $class_name;
}

/**
 * Return a clone of the object
 *
 * @param $object
 * @return mixed
 */
function    with_clone($object)
{
    return with(clone($object));
}