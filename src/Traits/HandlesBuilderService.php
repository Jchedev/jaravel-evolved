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
     * @param null $modifiers
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function findFromService(BuilderService $service, $id, $modifiers = null)
    {
        $modifiers = $this->makeModifiers($modifiers);

        return $service->find($id, $modifiers);
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param $id
     * @param null $modifiers
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function findOrFailFromService(BuilderService $service, $id, $modifiers = null)
    {
        $item = $this->findFromService($service, $id, $modifiers);

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

        if (is_null($limit = array_get($inputs, 'limit')) || (property_exists($this, 'limit_max') && !is_null($this->limit_max) && (int)$limit > $this->limit_max)) {
            $inputs['limit'] = property_exists($this, 'limit_default') ? $this->limit_default : null;
        }

        return new Modifiers($inputs);
    }

    /*
        EXAMPLE where we use the "request() + set params" by default

        protected function makeModifiers($data = null)
       {
            if (is_null($data) || is_array($data)) {
                $data_from_request = request()->all();

                $data = array_replace_recursive($data_from_request, is_array($data) ? $data : []);
            }

            return $this->traitMakeModifiers($data);
        }
     */
}