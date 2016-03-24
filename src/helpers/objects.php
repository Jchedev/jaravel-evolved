<?php

/**
 * Get the class name of an object (without namespace)
 *
 * @param $object
 * @return string
 */
function get_class_basename($object)
{
    $class_name = is_object($object) ? get_class($object) : $object;

    if ($pos = strrpos($class_name, '\\')) {
        return substr($class_name, $pos + 1);
    }

    return $class_name;
}

/**
 * Get the namespace part of the classname
 *
 * @param $object
 * @return null|string
 */
function get_class_namespace($object)
{
    $class_name = is_object($object) ? get_class($object) : $object;

    if ($pos = strrpos($class_name, '\\')) {
        return substr($class_name, 0, $pos);
    }

    return null;
}
