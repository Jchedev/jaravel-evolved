<?php

namespace Jchedev\Laravel\Traits;

trait HasReference
{
    public $referenceColumn = 'reference';

    /**
     * Boot the trait
     */
    protected static function bootHasReference()
    {
        static::creating(function ($model) {

            $referenceColumn = $model->referenceColumn;

            if (is_null($model->$referenceColumn)) {
                $model->$referenceColumn = $model->generateReference();
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
        return \Illuminate\Support\Str::uuid();
    }

    /**
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->referenceColumn;
    }
}