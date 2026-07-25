<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return $this->successResponse(
            $this->authService->issueToken($user),
            'Registration successful.',
            201
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->authenticate($request->validated());

        if ($user === null) {
            return $this->errorResponse('The provided credentials are incorrect.', 401);
        }

        return $this->successResponse($this->authService->issueToken($user), 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->revokeCurrentToken($request->user());

        return $this->successResponse(message: 'Logout successful.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse($request->user()->load('role'));
    }
}
