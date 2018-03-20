<?php

namespace Jchedev\Laravel\Http\Resources;

use \Illuminate\Http\Resources\Json\ResourceCollection;

class Collection extends ResourceCollection
{
    /**
     * @var bool
     */
    public $allow_eloquent_collection = true;

    /**
     * @param mixed $resource
     * @return mixed
     */
    protected function collectResource($resource)
    {
        if ($this->allow_eloquent_collection === true && $resource instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->collection = $resource;

            return $this->collection;
        }

        return parent::collectResource($resource);
    }
}