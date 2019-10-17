<?php

namespace Jchedev\Laravel\Eloquent\Collections;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
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
     *
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
}