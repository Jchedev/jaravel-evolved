<?php

namespace Jchedev\Laravel\Http\Resources;

class Resource extends \Illuminate\Http\Resources\Json\Resource
{
    /**
     * These are the requested includes
     *
     * @var array
     */
    protected $requestedIncludes = [];

    /**
     * List of includes that are always enable for this resource
     *
     * @var array
     */
    static $defaultIncludes = [];

    /**
     * List of includes that allowed for this resource
     *
     * @var array
     */
    static $availableIncludes = [];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        return parent::toArray($request);
    }

    /**
     * @param $include
     * @return $this
     */
    public function addIncludes($include)
    {
        $this->requestedIncludes = array_merge($this->requestedIncludes, (array)$include);

        return $this;
    }

    /**
     * @return array
     */
    public function getIncludesData()
    {
        $data = [];

        $includes = array_merge($this->requestedIncludes, static::$defaultIncludes);

        foreach ($includes as $include) {
            $methodName = 'includes' . ucfirst($include);

            if (in_array($include, static::$availableIncludes) && method_exists($this, $methodName)) {
                $data[$include] = $this->$methodName();
            }
        }

        return $data;
    }

    /**
     * @param $id
     * @param array $attributes
     * @return array
     */
    public function format($id, array $attributes = [])
    {
        $return = [
            'id'         => $id,
            'attributes' => $attributes
        ];

        if (count($includesData = $this->getIncludesData()) != 0) {
            $return['relationships'] = $includesData;
        }

        return $return;
    }
}