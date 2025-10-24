<?php

use App\Exceptions\Handler as GenericHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\InvalidDateRangeException;

// use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof InvalidDateRangeException) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    "huh" => "BRUH",
                    'type'    => get_class($e),
                ], 400);
            }
            if ($request->is('api/*')) {
                $status = 500;
                if ($e instanceof HttpException) {
                    $status = $e->getStatusCode();
                }

                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                ], $status);
            }
        });
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })->create();
