<?php
/**
 * Created by PhpStorm.
 * User: jchedev
 * Date: 18/03/2018
 * Time: 15:04
 */

namespace Jchedev\Laravel\Traits;

use Illuminate\Http\Request;
use Jchedev\Laravel\Classes\BuilderServices\BuilderService;
use Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers;
use Jchedev\Laravel\Exceptions\UnexpectedClassException;

trait HandlesBuilderService
{
    protected $limit_max = null;

    protected $limit_default = null;

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

        if (is_null($limit = array_get($inputs, 'limit')) || (!is_null($this->limit_max) && $limit > $this->limit_max)) {
            $inputs['limit'] = $this->limit_default;
        }

        return new Modifiers($inputs);
    }
}