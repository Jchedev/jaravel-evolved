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
        $previousOffset = $this->currentPage() - $this->count();
        $nextOffset = $this->currentPage() + $this->count();

        return [
            'data'            => $this->items->toArray(),
            'total'           => $this->total(),
            'count'           => $this->count(),
            'limit'           => $this->perPage(),
            'offset'          => $this->currentPage(),
            'previous_offset' => ($previousOffset < 0 ? null : $previousOffset),
            'next_offset'     => ($nextOffset >= $this->total() ? null : $nextOffset)
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

        return $this->isValidPageNumber($currentPage) ? (int)$currentPage : 0;
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