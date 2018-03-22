<?php

namespace Jchedev\Laravel\Http\Resources;

use \Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Jchedev\Laravel\Classes\Pagination\ByOffsetLengthAwarePaginator;

class Collection extends ResourceCollection
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        if ($this->resource instanceof ByOffsetLengthAwarePaginator) {
            return $this->toResponseForByOffsetPaginator($request);
        }

        if ($this->resource instanceof AbstractPaginator) {
            return $this->toResponseForAbstractPaginator($request);
        }

        return parent::toResponse($request);
    }

    /**
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function toResponseForByOffsetPaginator($request)
    {
        return parent::toResponse($request);
    }

    /**
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function toResponseForAbstractPaginator($request)
    {
        return parent::toResponse($request);
    }
}