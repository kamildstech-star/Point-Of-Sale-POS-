<?php

use App\Http\Middleware\Validuser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/storefront.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->append(\App\Http\Middleware\RequestTrace::class);
        // $middleware->append(Validuser::class);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request, $exception) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (Throwable $exception, Illuminate\Http\Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            if ($exception instanceof Illuminate\Validation\ValidationException) {
                return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors()], 422);
            }
            if ($exception instanceof \App\Domain\Operations\Conflict) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }
            $status = $exception instanceof Illuminate\Auth\AuthenticationException ? 401
                : ($exception instanceof Illuminate\Auth\Access\AuthorizationException ? 403
                : ($exception instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $exception->getStatusCode() : 500));
            if ($exception instanceof Illuminate\Database\UniqueConstraintViolationException) {
                $status = 409;
            }
            $messages = [401 => 'Unauthenticated.', 403 => 'Access denied.', 404 => 'Resource not found.', 405 => 'Method not allowed.', 409 => 'The operation conflicts with the current state. Select a business or review the record.', 419 => 'Your session expired. Please sign in again.', 429 => 'Too many requests. Please try again shortly.'];
            $headers = $exception instanceof Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $exception->getHeaders() : [];

            return response()->json(['message' => $messages[$status] ?? 'Unable to process your request.'], $status, $headers);
        });
    })->create();
