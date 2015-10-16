<?php

/**
 * Return the config value for the path of the images
 *
 * @param $for
 * @return mixed
 */
function config_images_path($for)
{
    return Config::get('services.media_storage.images.' . $for . '.path');
}

/**
 * Return the config value for the default image to
 *
 * @param $for
 * @return mixed
 */
function config_images_default($for)
{
    return Config::get('services.media_storage.images.' . $for . '.default_image');
}