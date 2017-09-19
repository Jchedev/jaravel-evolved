<?php

namespace Jchedev\Laravel\Traits;

trait HasReference
{
    /**
     * Boot the trait
     */
    protected static function bootHasReference()
    {
        static::creating(function ($model) {

            if (is_null($model->reference)) {
                $model->reference = $model->generateReference();
            }

            return $model;
        });
    }

    /**
     * Generate a Reference
     *
     * @return string
     */
    public function generateReference()
    {
        return uniqid();
    }
}