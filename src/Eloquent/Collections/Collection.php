<?php

namespace Jchedev\Laravel\Eloquent\Collections;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
    /*
     * New Methods
     */

    /**
     * Generate a builder with all the ID of the collections
     *
     * @return null
     */
    public function builder()
    {
        $firstElement = $this->first();

        if (is_null($firstElement)) {
            return null;
        }

        return $firstElement->newQuery()->whereIn($firstElement->getKeyName(), $this->modelKeys());
    }

    /**
     * Run an update on multiple elements at once through the builder
     *
     * @param array $attributes
     * @param array $options
     */
    public function update(array $attributes = [], array $options = [])
    {
        $perTypes = $this->groupBy(function ($element) {
            return get_class($element);
        });

        foreach ($perTypes as $class => $collection) {
            $builder = $collection->builder();

            if (!is_null($builder)) {
                $builder->update($attributes, $options);
            }
        }
    }

    /**
     * Run a delete on multiple elements at once through the builder
     */
    public function delete()
    {
        $perTypes = $this->groupBy(function ($element) {
            return get_class($element);
        });

        foreach ($perTypes as $class => $collection) {
            $builder = $collection->builder();

            if (!is_null($builder)) {
                $builder->delete();
            }
        }
    }

    /*
     * Modified methods
     */

    // ...
}