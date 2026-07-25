<?php

namespace App\Traits;

use App\Support\ApiResponse as ApiResponseFactory;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /** @param array<string, mixed>|object|null $data */
    protected function successResponse(
        array|object|null $data = null,
        string $message = 'Request successful',
        int $status = 200
    ): JsonResponse {
        return ApiResponseFactory::success($data, $message, $status);
    }

    /** @param array<string, mixed>|null $errors */
    protected function errorResponse(string $message, int $status, ?array $errors = null): JsonResponse
    {
        return ApiResponseFactory::error($message, $status, $errors);
    }
}
