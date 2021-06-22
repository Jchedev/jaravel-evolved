<?php

namespace Jchedev\Laravel\Eloquent\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Jchedev\Laravel\Eloquent\Builders\Builder;
use Jchedev\Laravel\Eloquent\Collections\Collection;
use Jchedev\Laravel\Interfaces\CollectionOrModel;

abstract class Model extends EloquentModel implements CollectionOrModel
{
    /*
     * New Methods
     */

    /**
     * Allow the call of getRouteKeyName() in a static way
     *
     * @return mixed
     */
    static function routeKeyName()
    {
        return with(new static)->getRouteKeyName();
    }

    /**
     * Allow the call of getRouteKeyName() in a static way
     *
     * @return mixed
     */
    static function keyName()
    {
        return with(new static)->getKeyName();
    }

    /**
     * Allow the call of getTable() in a static way
     *
     * @return mixed
     */
    static function table()
    {
        return with(new static)->getTable();
    }

    /**
     * Allow the call of getTableColumn() in a static way
     *
     * @param $column
     * @return mixed
     */
    static function tableColumn($column)
    {
        return with(new static)->getTableColumn($column);
    }

    /**
     * Allow the call of collection() in a static way
     *
     * @param array $models
     * @return mixed
     */
    static function collection(array $models = [])
    {
        return with(new static)->newCollection($models);
    }

    /**
     * Return the column concatenated to the table name
     *
     * @param $column
     * @return string
     */
    public function getTableColumn($column)
    {
        return $this->qualifyColumn($column);
    }

    /**
     * Because fireModelEvent is protected and cannot be called from outside the model
     * This is used by the JChedev\Eloquent\Builder - createMany() custom method
     *
     * @param $event
     * @return $this
     */
    public function applyEvent($event)
    {
        $this->fireModelEvent($event);

        return $this;
    }

    /**
     * Because updateTimestamps is protected and cannot be called from outside the model
     * This is used by the JChedev\Eloquent\Builder - createMany() custom method
     *
     * @return $this
     */
    public function applyTimestamps()
    {
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        return $this;
    }

    /**
     * Check if an attribute is set (different from null/empty)
     *
     * @param $attribute
     * @return bool
     */
    public function hasAttribute($attribute)
    {
        return array_key_exists($attribute, $this->attributes);
    }

    /**
     * @param $key
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @return $this|mixed
     */
    public function setModelAsAttribute($key, EloquentModel $model = null)
    {
        $methodName = Str::camel($key);

        if (method_exists($this, $methodName)) {
            $methodResponse = $this->$methodName();

            // Handle MorphTo relations
            if ($methodResponse instanceof MorphTo) {
                $this->setRelation($methodName, $model);

                parent::setAttribute($methodResponse->getMorphType(), !is_null($model) ? get_class($model) : null);

                parent::setAttribute($methodResponse->getForeignKeyName(), !is_null($model) ? $model->getKey() : null);

                return $this;
            }

            // Handle BelongsTo relations
            if ($methodResponse instanceof BelongsTo) {
                $this->setRelation($methodName, $model);

                return parent::setAttribute($methodResponse->getForeignKeyName(), !is_null($model) ? $model->getKey() : null);
            }
        }

        return parent::setAttribute($key, !is_null($model) ? $model->getKey() : null);
    }

    /*
     * Modified methods
     */

    /**
     * Overwrite the Eloquent\Builder by a custom one with even more features
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Overwrite the Eloquent\Collection by a custom one with even more features
     *
     * @param array $models
     * @return \Jchedev\Laravel\Eloquent\Collections\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * @param string $column
     * @return string
     */
    public function qualifyColumn($column)
    {
        if (!is_string($column)) {
            return $column;
        }

        return parent::qualifyColumn($column);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasSetMutator($key) === false && ($value instanceof EloquentModel || is_null($value))) {
            return $this->setModelAsAttribute($key, $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function count($value)
    {
        if (method_exists($this, $value)) {

            if (!array_key_exists($attributeKey = Str::snake($value) . '_count', $this->attributes)) {
                $this->loadCount($value);
            }

            return Arr::get($this->attributes, $attributeKey);
        }

        return parent::count($value);
    }
}