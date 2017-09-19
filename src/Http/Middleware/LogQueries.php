<?php

namespace Jchedev\Laravel\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class LogQueries
{
    /**
     * @param $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        $debug_enabled = Config::get('app.debug');

        // Step 1: We enable the SQL tracking
        if ($debug_enabled === true) {
            DB::enableQueryLog();
        }

        // Step 2: We execute the rest of the process
        $response = $next($request);

        // Step 3: We try to append the sql queries to the response
        if ($debug_enabled === true) {

            // We can append the debug to a JsonResponse without problem
            if (is_a($response, JsonResponse::class)) {
                $data = $response->getData();

                if (is_object($data)) {
                    $data->queries = DB::getQueryLog();
                    $response->setData($data);
                }
            }

            // Note: We could potentially handle more types of responses here ...

            DB::disableQueryLog();
        }

        return $response;
    }
}