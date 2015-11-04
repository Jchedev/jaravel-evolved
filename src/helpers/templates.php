<?php

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
    foreach (['phone' => ['s%', 'small'], 'tablet' => ['m%', 'medium'], 'desktop' => ['l%', 'large'], 'desktop-wide' => ['xl%', 'xlarge']] as $device => $class_format) {

        // Retrieve the correct value as a parameter
        if (is_int($value) || is_string($value)) {
            $current_value = $value;
        } else {
            if (is_array($value) && isset($value[$device])) {
                $current_value = $value[$device];
            }
        }

        // Add the css class to hide or with the correct width
        if ($current_value === false) {
            $classes[] = 'hide-on-' . $class_format[1] . '-only';
        } else {
            $current_value = isset($defined[$current_value]) ? ($grid_size / $defined[$current_value]) : $current_value;
            $classes[] = str_replace('%', $current_value, $class_format[0]);
        }
    }

    return implode(' ', $classes);
}

/**
 * Minify some HTML code by deleting comments and whitespaces
 *
 * @param $html
 * @return mixed
 */
function    minify_html($html)
{
    $html = preg_replace('/<!--([^\[|(<!)].*)/', '', $html);
    $html = preg_replace('/(?<!\S)\/\/\s*[^\r\n]*/', '', $html);
    $html = preg_replace('/\s{2,}/', '', $html);
    $html = preg_replace('/(\r?\n)/', '', $html);

    return $html;
}