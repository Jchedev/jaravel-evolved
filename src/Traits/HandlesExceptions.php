<?php

namespace Jchedev\Laravel\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait HandlesExceptions
{
    /**
     * @param \Exception $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function convertExceptionToJsonResponse(\Exception $exception)
    {
        $statusCode = $this->exceptionToStatusCode($exception);

        $array = $this->exceptionToArray($exception, $statusCode);

        return response()->json($array, $statusCode);
    }

    /**
     * @param \Exception $exception
     * @return int
     */
    protected function exceptionToStatusCode(\Exception $exception)
    {
        // Default Response (500 Http Code)
        $statusCode = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;

        if ($exception instanceof AuthorizationException) {
            // Status Code: 403
            $statusCode = HttpResponse::HTTP_FORBIDDEN;

        } elseif ($exception instanceof AuthenticationException) {
            // Status Code: 401
            $statusCode = HttpResponse::HTTP_UNAUTHORIZED;

        } elseif ($exception instanceof ValidationException) {
            // Status Code: 400
            $statusCode = HttpResponse::HTTP_BAD_REQUEST;

        } elseif ($exception instanceof ModelNotFoundException) {
            // Status Code: 404
            $statusCode = HttpResponse::HTTP_NOT_FOUND;

        } elseif ($exception instanceof HttpException) {
            // Status Code: Exception Status
            $statusCode = $exception->getStatusCode();
        }

        return $statusCode;
    }

    /**
     * @param \Exception $exception
     * @param $statusCode
     * @return array
     */
    protected function exceptionToArray(\Exception $exception, $statusCode)
    {
        $message = $exception->getMessage();

        // If the error is 500 and there is no debug, then we hide the specific error message
        if ($statusCode == HttpResponse::HTTP_INTERNAL_SERVER_ERROR && config('app.debug') === false) {
            $message = null;
        }

        $response = [
            'error' => [
                'status_code' => $statusCode,
                'message'     => empty($message) ? HttpResponse::$statusTexts[$statusCode] : $message
            ]
        ];

        if ($exception instanceof ValidationException) {
            $response['error']['errors'] = $exception->errors();
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ];
        }

        return $response;
    }
}