<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'wrap' => \App\Http\Middleware\FormatJsonResponse::class,
        ]);
        $middleware->appendToGroup('api', [\App\Http\Middleware\FormatJsonResponse::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (!$request->is('api/*')) return null;

            // 验证错误：统一为 { code:422, data:{ errors }, message }
            if ($e instanceof ValidationException) {
                /** @var ValidationException $e */
                $errors = $e->errors();
                // 取第一条错误作为 message
                $first = collect($errors)->flatten()->first() ?: 'Validation error';
                return response()->json([
                    'code' => 422,
                    'data' => ['errors' => $errors],
                    'message' => (string)$first,
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['code' => 401, 'data' => null, 'message' => 'Unauthenticated'], 401);
            }
            if ($e instanceof AuthorizationException) {
                return response()->json(['code' => 403, 'data' => null, 'message' => 'Forbidden'], 403);
            }
            if ($e instanceof ModelNotFoundException) {
                return response()->json(['code' => 404, 'data' => null, 'message' => 'Not Found'], 404);
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $msg = $e->getMessage() ?: 'Server Error';
            return response()->json(['code' => $status, 'data' => null, 'message' => $msg], $status);
        });
    })->create();
