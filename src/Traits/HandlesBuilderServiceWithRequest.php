<?php

namespace Jchedev\Laravel\Traits;

use Jchedev\Laravel\Classes\BuilderServices\BuilderService;

trait HandlesBuilderServiceWithRequest
{
    use HandlesBuilderService {
        HandlesBuilderService::createThroughService as traitCreateThroughService;
        HandlesBuilderService::makeModifiers as traitMakeModifiers;
    }

    /**
     * @param \Jchedev\Laravel\Classes\BuilderServices\BuilderService $service
     * @param array $data
     * @return mixed
     */
    public function createThroughService(BuilderService $service, array $data = [])
    {
        $data = array_replace_recursive(request()->all(), $data);

        return $this->traitCreateThroughService($service, $data);
    }

    /**
     * @param null $data
     * @return \Jchedev\Laravel\Classes\BuilderServices\Modifiers\Modifiers|null
     */
    protected function makeModifiers($data = null)
    {
        if (is_null($data) || is_array($data)) {
            $data_from_request = request()->only(['limit', 'offset', 'filters', 'sort', 'sort_order']);

            if (isset($data_from_request['filters']) && !is_array($data_from_request['filters'])) {
                $filters = json_decode($data_from_request['filters'], true);
                if (is_array($filters)) {
                    $data_from_request['filters'] = $filters;
                }
            }

            $data = array_replace_recursive($data_from_request, is_array($data) ? $data : []);
        }

        return $this->traitMakeModifiers($data);
    }
}