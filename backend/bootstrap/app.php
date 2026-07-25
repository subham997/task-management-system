<?php

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $exception, $request) {
            return $request->is('api/*')
                ? ApiResponse::error('The given data was invalid.', 422, $exception->errors())
                : null;
        });

        $exceptions->render(function (AuthenticationException $exception, $request) {
            return $request->is('api/*')
                ? ApiResponse::error('Unauthenticated.', 401)
                : null;
        });

        $exceptions->render(function (AuthorizationException $exception, $request) {
            return $request->is('api/*')
                ? ApiResponse::error('This action is unauthorized.', 403)
                : null;
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, $request) {
            return $request->is('api/*')
                ? ApiResponse::error('This action is unauthorized.', 403)
                : null;
        });

        $exceptions->render(function (ThrottleRequestsException $exception, $request) {
            return $request->is('api/*')
                ? ApiResponse::error('Too many requests.', 429)
                : null;
        });

        $exceptions->render(function (ModelNotFoundException $exception, $request) {
            return $request->is('api/*')
                ? ApiResponse::error('The requested resource was not found.', 404)
                : null;
        });

        $exceptions->render(function (NotFoundHttpException $exception, $request) {
            return $request->is('api/*')
                ? ApiResponse::error('The requested resource was not found.', 404)
                : null;
        });

        $exceptions->render(function (QueryException $exception, $request) {
            Log::error('database.query_failed', [
                'connection' => $exception->getConnectionName(),
                'exception' => $exception,
            ]);

            return $request->is('api/*')
                ? ApiResponse::error('The request could not be processed.', 500)
                : null;
        });

        $exceptions->render(function (Throwable $exception, $request) {
            Log::critical('application.unhandled_exception', [
                'exception' => $exception,
            ]);

            return $request->is('api/*')
                ? ApiResponse::error('An unexpected error occurred.', 500)
                : null;
        });
    })->create();
