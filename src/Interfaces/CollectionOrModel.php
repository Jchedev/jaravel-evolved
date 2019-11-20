<?php

namespace Jchedev\Laravel\Interfaces;

interface CollectionOrModel
{
    /**
     * @param $relations
     * @return mixed
     */
    public function loadMissing($relations);
}