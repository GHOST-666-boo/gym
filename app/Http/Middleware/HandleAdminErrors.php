<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class HandleAdminErrors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Admin operation error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->url(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'input' => $request->except(['password', 'password_confirmation', '_token'])
            ]);

            // Handle different types of errors
            if ($e instanceof \Illuminate\Database\QueryException) {
                return redirect()->back()
                    ->with('error', 'A database error occurred. Please check your input and try again.')
                    ->withInput();
            }

            if ($e instanceof \Illuminate\Http\Exceptions\PostTooLargeException) {
                return redirect()->back()
                    ->with('error', 'The uploaded file is too large. Please try a smaller file.')
                    ->withInput();
            }

            if ($e instanceof \Symfony\Component\HttpFoundation\File\Exception\FileException) {
                return redirect()->back()
                    ->with('error', 'File upload failed. Please try again with a different file.')
                    ->withInput();
            }

            // Generic error handling
            return redirect()->back()
                ->with('error', 'An unexpected error occurred. Please try again.')
                ->withInput();
        }
    }
}