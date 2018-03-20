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
                return call_user_func_array([$first_element, $scope_method], array_merge([$this->builder()], $parameters));
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Load the relations only where it is missing, without deleting all the previous ones
     *
     * @param $relations
     * @return $this
     */
    public function loadMissing($relations)
    {
        $items_without_relations = new \Illuminate\Database\Eloquent\Collection();

        if (count($this->items) > 0) {
            if (is_string($relations)) {
                $relations = func_get_args();
            }

            foreach ($this->items as $item) {

                // Check that the Item has all the relations loaded

                foreach ($relations as $relation) {
                    if ($item->relationLoaded($relation) === false) {
                        $items_without_relations->push($item);
                        break;
                    }
                }
            }

            // Load missing relations

            $items_without_relations->load($relations);
        }

        return $this;
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