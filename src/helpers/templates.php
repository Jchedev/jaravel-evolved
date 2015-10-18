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
    $defined = ['full' => 1, 'half' => 2, 'third' => 3, 'fourth' => 4];

    $current_value = 'full';
    foreach (['phone' => 's%', 'tablet' => 'm%', 'desktop' => 'l%'] as $device => $class_format) {
        if (is_int($value) || is_string($value)) {
            $current_value = $value;
        } else {
            if (is_array($value) && isset($value[$device])) {
                $current_value = $value[$device];
            }
        }

        $current_value = isset($defined[$current_value]) ? ($grid_size / $defined[$current_value]) : $current_value;

        $classes[] = str_replace('%', $current_value, $class_format);
    }

    return implode(' ', $classes);
}