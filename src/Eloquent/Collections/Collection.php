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
}