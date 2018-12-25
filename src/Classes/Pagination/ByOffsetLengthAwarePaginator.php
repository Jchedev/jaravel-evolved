<?php

namespace Jchedev\Laravel\Classes\Pagination;

use Illuminate\Pagination\LengthAwarePaginator;

class ByOffsetLengthAwarePaginator extends LengthAwarePaginator
{
    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'data'        => $this->items->toArray(),
            'total'       => $this->total(),
            'count'       => $this->count(),
            'limit'       => $this->perPage(),
            'offset'      => $this->currentPage(),
            'next_offset' => ($next_offset = ($this->currentPage() + $this->count())) >= $this->total() ? null : $next_offset
        ];
    }

    /**
     * @param int $currentPage
     * @param string $pageName
     * @return int
     */
    protected function setCurrentPage($currentPage, $pageName)
    {
        $currentPage = !is_null($currentPage) ? $currentPage : static::resolveCurrentPage($pageName);

        return $this->isValidPageNumber($currentPage) ? (int)$currentPage : 1;
    }

    /**
     * @param int $page
     * @return bool
     */
    protected function isValidPageNumber($page)
    {
        return $page >= 0 && filter_var($page, FILTER_VALIDATE_INT) !== false;
    }
}