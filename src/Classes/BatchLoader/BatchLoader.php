<?php

namespace Jchedev\Laravel\Classes\BatchLoader;

/**
 * Used to apply logic on a system of small batches
 */
class BatchLoader
{
    protected $logic;

    protected $initial;

    protected $onResults = null;

    /**
     * @param callable $logic
     * @param null $initial
     */
    public function __construct(callable $logic, $initial = null)
    {
        $this->logic = $logic;

        $this->initial = $initial;
    }

    /**
     * Apply an initial modifier on the results before doing anything else
     *
     * @param callable $logic
     * @return $this
     */
    public function onResults(callable $logic): self
    {
        $this->onResults = $logic;

        return $this;
    }

    /**
     * Apply the specified logic on each "batch of results" returned
     *
     * @param callable $each
     */
    public function each(callable $each)
    {
        $offset = $this->initial;

        do {
            $previousOffset = $offset;

            $results = call_user_func_array($this->logic, [&$offset]);

            if (!is_null($this->onResults)) {
                $results = call_user_func_array($this->onResults, [$results]);
            }

            $response = call_user_func_array($each, [$results, $previousOffset]);

            if ($previousOffset === $offset || $response === false) {
                $offset = false;
            }

        } while ($offset !== false);
    }
}
