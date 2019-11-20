<?php

namespace Jchedev\Laravel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    /**
     * @param mixed $resource
     * @return \Jchedev\Laravel\Http\Resources\Collection
     */
    public static function collection($resource)
    {
        return new Collection($resource, static::class);
    }
}