<?php

namespace Jchedev\Laravel\Http\Resources;

class Resource extends \Illuminate\Http\Resources\Json\Resource
{
    /**
     * @var array
     */
    static $default_includes = [];

    /**
     * @var array
     */
    static $always_load = [];

    /**
     * @param $id
     * @param array $attributes
     * @return array
     */
    public function format($id, array $attributes = [])
    {
        $return = [
            'id'            => $id,
            'attributes'    => $attributes,
            'relationships' => []
        ];

        foreach (static::$default_includes as $include => $resource) {
            if (class_exists($resource)) {
                $return['relationships'][$include] = new $resource($this->$include);
            }
        }

        return $return;
    }

    /**
     * @param mixed $resource
     * @param bool $with_auto_load
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    static function collection($resource, $with_auto_load = true)
    {
        if ($with_auto_load === true) {
            $resource->loadMissing(static::relationstoLoad());
        }

        return parent::collection($resource);
    }

    /**
     * @param null $path
     * @return array
     */
    static function relationsToLoad($path = null)
    {
        $all_includes = [];

        foreach (static::$always_load as $relation) {
            $all_includes[] = $path . (is_null($path) ? '' : '.') . $relation;
        }

        foreach (static::$default_includes as $include => $resource) {
            $new_path = $path . (is_null($path) ? '' : '.') . $include;

            $all_includes[] = $new_path;

            if (class_exists($resource)) {
                $all_includes = array_merge($all_includes, $resource::relationsToLoad($new_path));
            }
        }

        return $all_includes;
    }
}