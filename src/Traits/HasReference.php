<?php

namespace Jchedev\Laravel\Traits;

trait HasReference
{
    protected $referenceColumn = 'reference';

    /**
     * Boot the trait
     */
    protected static function bootHasReference()
    {
        static::creating(function ($model) {

            $referenceColumn = $model->getReferenceColumn();

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
        return (string)\Illuminate\Support\Str::uuid();
    }

    /**
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->getReferenceColumn();
    }

    /**
     * @return string
     */
    public function getReferenceColumn()
    {
        return $this->referenceColumn;
    }
}