<?php

namespace Jchedev\Laravel\Http\Resources;

use \Illuminate\Http\Resources\Json\ResourceCollection;

class Collection extends ResourceCollection
{
    /**
     * ResourceCollection constructor.
     *
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }
}