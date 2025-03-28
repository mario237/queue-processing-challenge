<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'status' => $statusCode,
            'locale' => App::getLocale(),
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function error(
        string $message = 'Error',
        int $statusCode = 400,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'status' => $statusCode,
            'locale' => App::getLocale(),
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
