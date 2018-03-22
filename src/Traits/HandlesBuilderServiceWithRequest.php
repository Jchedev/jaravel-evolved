<?php

namespace Jchedev\Laravel\Traits;

use Jchedev\Laravel\Classes\BuilderServices\BuilderService;

trait HandlesBuilderServiceWithRequest
{
    use HandlesBuilderService {
        HandlesBuilderService::createFromService as traitCreateFromService;
        HandlesBuilderService::makeModifiers as traitMakeModifiers;
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param array $data
     * @return mixed
     */
    public function createFromService(BuilderService $service, array $data = [])
    {
        $data = array_replace_recursive(request()->all(), $data);

        return $this->traitCreateFromService($service, $data);
    }

    /**
     * @param null $data
     * @return \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null
     */
    protected function makeModifiers($data = null)
    {
        if (is_null($data) || is_array($data)) {
            $data_from_request = request()->all();

            $data = array_replace_recursive($data_from_request, is_array($data) ? $data : []);
        }

        return $this->traitMakeModifiers($data);
    }
}