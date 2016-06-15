<?php

namespace Jchedev\Laravel\Eloquent\Collections;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
    /**
     * Try different features before calling the parent __call method
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $first_element = $this->first();

        if (!is_null($first_element)) {

            // Try to apply a Scope Method on a builder representing the collection
            $scope_method = 'scope' . ucfirst($method);

            if (method_exists($first_element, $scope_method)) {
                return call_user_func_array([$first_element, $scope_method], array_merge([$this->builder()], $parameters ));
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Generate a builder with all the ID of the collections
     *
     * @return null
     */
    public function builder()
    {
        $first_element = $this->first();

        if (is_null($first_element)) {
            return null;
        }

        return $first_element->newQuery()->whereIn($first_element->getKeyName(), $this->modelKeys());
    }
}