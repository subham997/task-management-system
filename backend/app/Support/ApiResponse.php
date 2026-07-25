<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /** @param array<string, mixed>|object|null $data */
    public static function success(
        array|object|null $data = null,
        string $message = 'Request successful',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /** @param array<string, mixed>|null $errors */
    public static function error(
        string $message,
        int $status,
        ?array $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?? (object) [],
        ], $status);
    }
}
