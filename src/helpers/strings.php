<?php

/**
 * Sanitize a string by triming it and removing weird characters
 *
 * @param $value
 * @return string
 */
function    sanitize_string($value)
{
    if (!is_null($value)) {
        $value = trim($value);
    }

    return $value;
}

/**
 * Convert a boolean value into the matching string
 *
 * @param $value
 * @param string $true
 * @param string $false
 * @return string
 */
function    boolean_to_string($value, $true = 'true', $false = 'false')
{
    return ($value) ? $true : $false;
}

/**
 * Transform an empty string/array into NULL
 *
 * @param $value
 * @return array|null
 */
function    null_if_empty($value)
{
    if (is_array($value)) {
        foreach ($value as $key => $line) {
            $value[$key] = null_if_empty($line);
        }

        return $value;
    }

    return !empty($value) ? $value : null;
}

/**
 * Convert a string of duration into a value of $convert_in (minute, second, etc...)
 *
 * @param $string
 * @param string $convert_in
 * @return bool
 */
function    time_duration($string, $convert_in = 'second')
{
    if (preg_match('/^([0-9]+) (.*)$/', $string, $parts) == 0 || ($time_multiplier = time_multiplier($parts[2], $convert_in)) === false) {
        return false;
    }

    return $parts[1] * $time_multiplier;
}

/**
 * Return the time multiplier from X (minute, second) to Y (minute, second) or false if invalid
 *
 * @param $from
 * @param $to
 * @return bool|int
 */
function    time_multiplier($from, $to)
{
    // Convert the $from value to something valid
    $from = strtolower($from);
    if ($from{strlen($from) - 1} == 's') {
        $from = substr($from, 0, -1);
    }

    // Convert the $to value to something valid
    $to = strtolower($to);
    if ($to{strlen($to) - 1} == 's') {
        $to = substr($to, 0, -1);
    }

    // Try to return a specific multiplier
    switch ($from) {

        // Convert from Seconds to ...
        case 'second':
            switch ($to) {
                case 'minute':
                    return (1 / 60);
                    break;

                case 'hour':
                    return (1 / 60 / 60);
                    break;
            }
            break;

        // Convert from Minutes to ...
        case 'minute':
            switch ($to) {
                case 'second':
                    return 60;
                    break;

                case 'hour':
                    return (1 / 60);
                    break;
            }
            break;


        // Convert from Hours to ...
        case 'hour':
            switch ($to) {
                case 'second':
                    return 60 * 60;
                    break;

                case 'minute':
                    return 60;
                    break;
            }
            break;
    }

    return ($from == $to) ? 1 : false;
}