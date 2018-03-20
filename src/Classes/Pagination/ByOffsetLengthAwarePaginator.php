<?php

namespace Jchedev\Laravel\Classes\Pagination;

use Illuminate\Pagination\LengthAwarePaginator;

class ByOffsetLengthAwarePaginator extends LengthAwarePaginator
{
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
}