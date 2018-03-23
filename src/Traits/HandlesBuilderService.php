<?php
/**
 * Created by PhpStorm.
 * User: jchedev
 * Date: 18/03/2018
 * Time: 15:04
 */

namespace Jchedev\Laravel\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Jchedev\Laravel\Classes\BuilderServices\BuilderService;
use Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers;
use Jchedev\Laravel\Exceptions\UnexpectedClassException;

trait HandlesBuilderService
{
    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param array $data
     * @return mixed
     */
    public function createFromService(BuilderService $service, array $data = [])
    {
        return $service->create($data);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param null $modifiers
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getFromService(BuilderService $service, $modifiers = null)
    {
        $modifiers = $this->makeModifiers($modifiers);

        return $service->get($modifiers);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param null $modifiers
     * @return \Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator
     */
    public function paginateFromService(BuilderService $service, $modifiers = null)
    {
        $modifiers = $this->makeModifiers($modifiers);

        $modifiers = $modifiers ?: new Modifiers();

        $default_limit = property_exists($this, 'pagination_limit_default') ? $this->pagination_limit_default : null;

        if (is_null($limit = $modifiers->getLimit()) || (isset($this->pagination_limit_max) && $this->pagination_limit_max < $limit)) {
            $modifiers->limit($default_limit);
        }

        return $service->paginate($modifiers);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param null $modifiers
     * @return int
     */
    public function countFromService(BuilderService $service, $modifiers = null)
    {
        $modifiers = $this->makeModifiers($modifiers);

        return $service->count($modifiers);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param $id
     * @param null $key
     * @param null $modifiers
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function findFromService(BuilderService $service, $id, $key = null, $modifiers = null)
    {
        $modifiers = $this->makeModifiers($modifiers);

        return $service->find($id, $key, $modifiers);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param $id
     * @param null $key
     * @param null $modifiers
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function findOrFailFromService(BuilderService $service, $id, $key = null, $modifiers = null)
    {
        $item = $this->findFromService($service, $id, $key, $modifiers);

        if (is_null($item)) {
            throw new ModelNotFoundException();
        }

        return $item;
    }

    /**
     * @param null $data
     * @return \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null
     */
    public function makeModifiers($data = null)
    {
        if (is_null($data) || $data instanceof Modifiers) {
            return $data;
        }

        if ($data instanceof Request) {
            $inputs = $data->all();
        } elseif (is_array($data)) {
            $inputs = $data;
        } else {
            throw new UnexpectedClassException($data, [Modifiers::class, Request::class, []]);
        }

        return new Modifiers($inputs);
    }
}