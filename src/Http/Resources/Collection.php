<?php

namespace Jchedev\Laravel\Http\Resources;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class Collection extends AnonymousResourceCollection
{
    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->collects, $method)) {
            foreach ($this->collection as $object) {
                call_user_func_array([$object, $method], $parameters);
            }

            return $this;
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function addMeta($key, $value)
    {
        $additional = Arr::add($this->additional, 'meta.' . $key, $value);

        return $this->additional($additional);
    }
}