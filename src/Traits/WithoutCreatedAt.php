<?php
/**
 * Created by PhpStorm.
 * User: jeanfrancoischedeville
 * Date: 13/10/2015
 * Time: 15:48
 */

namespace Jchedev\Laravel\Traits;

trait WithoutCreatedAt
{
    /**
     * Setter for updated_at
     *
     * @param $value
     */
    public function setCreatedAtAttribute($value)
    {
        // Disabled
    }

    /**
     * Getter for updated_at column name
     */
    public function getCreatedAtColumn()
    {
        // Disabled
    }

    /**
     * Return the list of dates - without updated_at
     *
     * @return array
     */
    public function getDates()
    {
        return ['updated_at'];
    }
}