<?php

namespace Jchedev\Laravel\Traits;

trait HasReference
{
    public $referenceColumn = 'reference';

    public $canBeFoundThroughReference = true;

    /**
     * @param $string
     * @return bool
     */
    static function isReference($string)
    {
        return preg_match('/^[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}$/', $string) == 1;
    }

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
}