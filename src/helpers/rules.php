<?php

function rules_require_one_of(array $rules)
{
    $keys = array_keys($rules);

    if (count($keys) == 1) {
        $rules[$keys[0]][] = 'required';
    } else {
        foreach ($rules as $key => $rule) {
            $rules[$key][] = 'required_without_all:' . implode(array_diff($keys, [$key]), ',');
        }
    }

    return $rules;
}