<?php

namespace Jchedev\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // Force the Request to ONLY accept JSON
            $request->headers->add([
                'Accept'           => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest'
            ]);

            // Execute rest of the logic in a row
            $response = $next($request);

            // If the response returned is not a valid one (not 200->300), we convert it
            if ($response->isSuccessful() === false) {
                $data = method_exists($response, 'getData') ? $response->getData(true) : null;
                $response = $this->generateInvalidResponse($response->getStatusCode(), null, $data);
            }
        }
        catch (HttpException $e) {
            $response = $this->generateInvalidResponse($e->getStatusCode(), $e->getMessage());
        }
        catch (\Exception $e) {
            $message = Config::get('app.debug') ? $e->getMessage() : null;
            $response = $this->generateInvalidResponse(\Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR, $message);
        }

        return (is_a($response, JsonResponse::class) ? $response : Response::json($response->getOriginalContent(), $response->getStatusCode()));
    }

    /**
     * Generate the format for an invalid Json Response
     *
     * @param $code
     * @param null $message
     * @param array|null $data
     * @return mixed
     */
    private function    generateInvalidResponse($code, $message = null, Array $data = null)
    {
        if (empty($message) === true) {
            $message = \Illuminate\Http\Response::$statusTexts[$code];
        }

        $response_content = ['error' => $message];
        if (is_array($data)) {
            $response_content['data'] = $data;
        }

        return Response::json($response_content, $code);
    }
}