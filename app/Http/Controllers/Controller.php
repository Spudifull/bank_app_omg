<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;


abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Общий метод для ответа API с данными.
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function apiResponse(mixed $data, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => $status === 200,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Общий метод для ответа API с сообщением об ошибке.
     *
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function apiError(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'error' => $message,
        ], $status);
    }
}
