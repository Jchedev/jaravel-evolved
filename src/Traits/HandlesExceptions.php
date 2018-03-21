<?php

namespace Jchedev\Laravel\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
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
        $status_code = $this->exceptionToStatusCode($exception);

        $array = $this->exceptionToArray($exception, $status_code);

        return response()->json($array, $status_code);
    }

    /**
     * @param \Exception $exception
     * @return int
     */
    protected function exceptionToStatusCode(\Exception $exception)
    {
        // Default Response (500 Http Code)
        $status_code = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;

        if ($exception instanceof AuthorizationException) {
            // Status Code: 403
            $status_code = HttpResponse::HTTP_FORBIDDEN;

        } elseif ($exception instanceof AuthenticationException) {
            // Status Code: 401
            $status_code = HttpResponse::HTTP_UNAUTHORIZED;

        } elseif ($exception instanceof ValidationException) {
            // Status Code: 400
            $status_code = HttpResponse::HTTP_BAD_REQUEST;

        } elseif ($exception instanceof HttpException) {
            // Status Code: Exception Status
            $status_code = $exception->getStatusCode();
        }

        return $status_code;
    }

    /**
     * @param \Exception $exception
     * @param $status_code
     * @return array
     */
    protected function exceptionToArray(\Exception $exception, $status_code)
    {
        $message = $exception->getMessage();

        if ($status_code == HttpResponse::HTTP_INTERNAL_SERVER_ERROR && config('app.debug') === false) {
            $message = null;
        }

        $response = [
            'error' => [
                'status_code' => $status_code,
                'message'     => empty($message) ? HttpResponse::$statusTexts[$status_code] : $message
            ]
        ];

        if ($exception instanceof ValidationException) {
            $response['error']['errors'] = $exception->errors();
        }

        return $response;
    }
}