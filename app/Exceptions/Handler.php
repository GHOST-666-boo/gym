<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        // Handle 404 errors for products specifically
        if ($e instanceof ModelNotFoundException && $e->getModel() === 'App\\Models\\Product') {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Product not found',
                    'message' => 'The requested product could not be found.'
                ], 404);
            }
            
            return response()->view('errors.404', [], 404);
        }

        // Handle general 404 errors
        if ($e instanceof NotFoundHttpException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Page not found',
                    'message' => 'The requested page could not be found.'
                ], 404);
            }
            
            return response()->view('errors.404', [], 404);
        }

        // Handle file upload errors
        if ($e instanceof \Illuminate\Http\Exceptions\PostTooLargeException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'File too large',
                    'message' => 'The uploaded file is too large. Please try a smaller file.'
                ], 413);
            }
            
            return redirect()->back()
                ->with('error', 'The uploaded file is too large. Please try a smaller file.')
                ->withInput();
        }

        // Handle database connection errors
        if ($e instanceof \Illuminate\Database\QueryException) {
            \Log::error('Database error: ' . $e->getMessage(), [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'url' => $request->url(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Database error',
                    'message' => 'A database error occurred. Please try again later.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'A database error occurred. Please try again later.')
                ->withInput();
        }

        // Handle validation errors for AJAX requests
        if ($e instanceof \Illuminate\Validation\ValidationException && $request->expectsJson()) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
        }

        return parent::render($request, $e);
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to access this resource.'
            ], 401);
        }

        // Redirect to login with intended URL
        return redirect()->guest(route('login'))
            ->with('info', 'Please log in to access this page.');
    }

    /**
     * Convert a validation exception into a JSON response.
     */
    protected function invalidJson($request, \Illuminate\Validation\ValidationException $exception): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Validation failed',
            'message' => $exception->getMessage(),
            'errors' => $exception->errors(),
        ], $exception->status);
    }
}