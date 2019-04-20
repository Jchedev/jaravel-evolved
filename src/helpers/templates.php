<?php

/**
 * Minify some HTML code by deleting comments and whitespaces
 *
 * @param $html
 * @return mixed
 */
function minify_html($html)
{
    $html = preg_replace('/<!--([^\[|(<!)].*)/', '', $html);

    $html = preg_replace('/(?<!\S)\/\/\s*[^\r\n]*/', '', $html);

    $html = preg_replace('/\s{2,}/', '', $html);

    $html = preg_replace('/(\r?\n)/', '', $html);

    return $html;
}