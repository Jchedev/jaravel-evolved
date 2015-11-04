<?php

/**
 * Return all the CSS classes to apply in one string
 *
 * @param null $value
 * @param string $for
 * @return string
 */
function    css_grid($value = null, $for = 'materialize')
{
    $grid = 12;
    $value = (!is_null($value) ? $value : 'full');
    $defined = ['full' => 1, 'half' => 2, 'third' => 3, 'fourth' => 4];

    $classes = [];
    switch ($for) {
        case 'materialize':
            $classes[] = 'col';
            $configuration = [
                'phone'        => ['s%', 'hide-on-small-only'],
                'tablet'       => ['m%', 'hide-on-medium-only'],
                'desktop'      => ['l%', 'hide-on-large-only'],
                'desktop-wide' => ['xl%', 'hide-on-xlarge-only']
            ];
            break;
    }

    if (!isset($configuration)) {
        // We don't know what to do so we return the input given for now
        $classes = array_merge($classes, (array)$value);
    } else {
        // We have a specific configuration to apply to the value(s)
        $current_value = 'full';

        foreach (['phone', 'tablet', 'desktop', 'desktop-wide'] as $device) {

            // Collect the value for $device
            if (is_int($value) || is_string($value)) {
                $current_value = $value;
            } elseif (is_array($value) && isset($value[$device])) {
                $current_value = $value[$device];
            }

            // Match the value to the configuration used
            if (isset($configuration[$device])) {
                $configuration_for_device = (array)$configuration[$device];


                // We decided to hide it
                if ($current_value === false) {
                    $classes[] = isset($configuration_for_device[1]) ? $configuration_for_device[1] : null;
                } // or We defined a width for it
                else {
                    $current_value = isset($defined[$current_value]) ? ($grid / $defined[$current_value]) : $current_value;
                    $classes[] = str_replace('%', $current_value, $configuration_for_device[0]);
                }
            }
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