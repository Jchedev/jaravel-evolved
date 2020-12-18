<?php

namespace Jchedev\Laravel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

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

    /**
     * @param $key
     * @param $value
     * @return \Jchedev\Laravel\Http\Resources\Resource
     */
    public function addMeta($key, $value)
    {
        $additional = Arr::add($this->additional, 'meta.' . $key, $value);

        return $this->additional($additional);
    }
}