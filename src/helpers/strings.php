<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Sanitize a string by triming it and removing weird characters
 *
 * @param $value
 * @return string
 */
function sanitize_string($value): string
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
function boolean_to_string($value, $true = 'true', $false = 'false'): string
{
    return ($value) ? $true : $false;
}

/**
 * Transform an empty string/array into NULL
 *
 * @param $value
 * @return array|null
 */
function null_if_empty($value)
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
 * @return bool|int
 */
function time_duration($string, $convert_in = 'second')
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
 * @return int
 */
function time_multiplier($from, $to): int
{
    $from = Str::singular(strtolower($from));

    $to = Str::singular(strtolower($to));

    if ($from == $to) {
        return 1;
    }

    $multipliers = [
        'second' => [
            'minute' => (1 / 60),
            'hour'   => (1 / 60 / 60)
        ],
        'minute' => [
            'second' => 60,
            'hour'   => (1 / 60)
        ],
        'hour'   => [
            'second' => 60 * 60,
            'minute' => 60
        ],
        'day'    => [
            'second' => 60 * 60 * 24,
            'minute' => 60 * 24,
            'hour'   => 24
        ],
        'week'   => [
            'second' => 60 * 60 * 24 * 7,
            'minute' => 60 * 24 * 7,
            'hour'   => 24 * 7,
            'day'    => 7
        ]
    ];

    return Arr::get($multipliers, $from . '.' . $to, false);
}

/**
 * Minify some HTML code by deleting comments and whitespaces
 *
 * @param $html
 * @return mixed
 */
function minify_html($html): string
{
    $html = preg_replace('/<!--([^\[|(<!)].*)/', '', $html);

    $html = preg_replace('/(?<!\S)\/\/\s*[^\r\n]*/', '', $html);

    $html = preg_replace('/\s{2,}/', '', $html);

    $html = preg_replace('/(\r?\n)/', '', $html);

    return $html;
}