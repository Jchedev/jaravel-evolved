<?php
/**
 * Created by PhpStorm.
 * User: jchedev
 * Date: 18/03/2018
 * Time: 15:04
 */

namespace Jchedev\Laravel\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    public function createThroughService(BuilderService $service, array $data = [])
    {
        return $service->create($data);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param null $modifiers
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getThroughService(BuilderService $service, $modifiers = null)
    {
        $modifiers = $this->makeModifiers($modifiers);

        return $service->get($modifiers);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param null $modifiers
     * @return \Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator
     */
    public function paginateThroughService(BuilderService $service, $modifiers = null)
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
    public function countThroughService(BuilderService $service, $modifiers = null)
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
    public function findThroughService(BuilderService $service, $id, $key = null, $modifiers = null)
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
    public function findOrFailThroughService(BuilderService $service, $id, $key = null, $modifiers = null)
    {
        $item = $this->findThroughService($service, $id, $key, $modifiers);

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

        if (!is_array($data)) {
            throw new UnexpectedClassException($data, [Modifiers::class, []]);
        }

        return new Modifiers($data);
    }
}