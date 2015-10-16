<?php

/**
 * Return the path for a CSS asset
 *
 * @param $path
 * @return string
 */
function    asset_css($path)
{
    return asset('css/' . $path);
}

/**
 * Return the path for Javascript asset
 *
 * @param $path
 * @return string
 */
function    asset_js($path)
{
    return asset('js/' . $path);
}

/**
 * Return all the CSS classes to apply in one string
 *
 * @param $value
 * @param int $grid_size
 * @return string
 */
function    css_grid($value, $grid_size = 12)
{
    $classes = ['col'];

    foreach (['phone' => 's%', 'tablet' => 'm%', 'desktop' => 'l%'] as $device => $class_format) {
        if (is_int($value)) {
            $grid_size = $value;
        } else {
            if (is_array($value) && isset($value[$device])) {
                $grid_size = $value[$device];
            }
        }
        $classes[] = str_replace('%', $grid_size, $class_format);
    }

    return implode(' ', $classes);
}