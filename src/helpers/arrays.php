<?php

/**
 * @param array $array
 * @param array $keys
 * @param null $default
 * @return mixed|null
 */
function array_get_any(array $array, array $keys, $default = null)
{
    foreach ($keys as $key) {
        if (!is_null($value = array_get($array, $key))) {
            return $value;
        }
    }

    return $default;
}