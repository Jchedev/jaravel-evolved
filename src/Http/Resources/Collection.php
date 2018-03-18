<?php

namespace Jchedev\Laravel\Http\Resources;

use \Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

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

            // This line is copied from Illuminate\Http\Resources\CollectResources
            return $resource instanceof AbstractPaginator ? $resource->setCollection($this->collection) : $this->collection;
        }

        return parent::collectResource($resource);
    }
}