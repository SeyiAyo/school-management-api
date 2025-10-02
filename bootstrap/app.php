<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authentication exceptions for API routes
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login to access this resource.',
                    'HttpStatusCode' => 401
                ], 401);
            }
        });
        
        // Handle ModelNotFoundException (direct model queries)
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $model = class_basename($e->getModel());
                
                return response()->json([
                    'success' => false,
                    'message' => $model . ' not found',
                    'HttpStatusCode' => 404
                ], 404);
            }
        });
        
        // Handle NotFoundHttpException (route model binding failures)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                // Extract model name from the error message
                $message = $e->getMessage();
                if (preg_match('/No query results for model \[(.+?)\]/', $message, $matches)) {
                    $modelClass = $matches[1];
                    $model = class_basename($modelClass);
                    
                    return response()->json([
                        'success' => false,
                        'message' => $model . ' not found',
                        'HttpStatusCode' => 404
                    ], 404);
                }
                
                // Fallback for other 404 errors
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'HttpStatusCode' => 404
                ], 404);
            }
        });
    })->create();
