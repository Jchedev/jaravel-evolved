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
            $data = array_replace_recursive($this->modifiersDataFromRequest(), is_array($data) ? $data : []);

            if (isset($data['filters'])) {
                $filters = [];

                foreach ($data['filters'] as $key => $value) {
                    $filters[] = [$key => $value];
                }

                $data['filters'] = $filters;
            }
        }

        return $this->traitMakeModifiers($data);
    }

    /**
     * @return array
     */
    protected function modifiersDataFromRequest()
    {
        $data_from_request = request()->only(['limit', 'offset', 'filters', 'sort', 'sort_order']);

        if (isset($data_from_request['filters'])) {

            // If $filters is a string, we try to json_decode it
            if (!is_array($data_from_request['filters'])) {
                $filters = json_decode($data_from_request['filters'], true);

                if (is_array($filters)) {
                    $data_from_request['filters'] = $filters;
                } else {
                    unset($data_from_request['filters']);
                }
            }

            // Ignore all the filters with empty values
            foreach ($data_from_request['filters'] as $key => $value) {
                if (!is_string($key) || $value == '' || (is_array($value) && count($value) == 0)) {
                    unset ($data_from_request['filters'][$key]);
                }
            }
        }

        return $data_from_request;
    }
}