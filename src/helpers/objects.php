<?php

/**
 * Get the class name of an object (without namespace)
 *
 * @param $object
 * @return string
 */
function get_class_basename($object)
{
    $className = is_object($object) ? get_class($object) : $object;

    if ($pos = strrpos($className, '\\')) {
        return substr($className, $pos + 1);
    }

    return $className;
}

/**
 * Get the namespace part of the classname
 *
 * @param $object
 * @return null|string
 */
function get_class_namespace($object)
{
    $className = is_object($object) ? get_class($object) : $object;

    if ($pos = strrpos($className, '\\')) {
        return substr($className, 0, $pos);
    }

    return null;
}
