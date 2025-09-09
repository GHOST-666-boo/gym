<?php

namespace App\Http\Middleware;

use App\Services\ImageProtectionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProtectedImageMiddleware
{
    protected ImageProtectionService $protectionService;

    public function __construct(ImageProtectionService $protectionService)
    {
        $this->protectionService = $protectionService;
    }

    /**
     * Handle an incoming request for protected images
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if protection is enabled
        if (!$this->protectionService->isProtectionEnabled()) {
            return $next($request);
        }

        // Validate token if present
        $token = $request->get('token');
        if ($token) {
            $validation = $this->protectionService->validateImageToken($token);
            
            if (!$validation['valid']) {
                Log::warning('Invalid image token access attempt', [
                    'token' => substr($token, 0, 20) . '...',
                    'error' => $validation['error'],
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                
                return response('Unauthorized', 401);
            }

            // Check if image file exists
            $imagePath = $validation['image_path'];
            if (!Storage::disk('public')->exists($imagePath)) {
                Log::warning('Protected image not found', [
                    'image_path' => $imagePath,
                    'ip' => $request->ip()
                ]);
                
                return response('Not Found', 404);
            }

            // Rate limiting check
            if ($this->isRateLimited($request)) {
                Log::warning('Rate limit exceeded for protected image access', [
                    'ip' => $request->ip(),
                    'image_path' => $imagePath
                ]);
                
                return response('Too Many Requests', 429);
            }

            // Log successful access
            $this->protectionService->logProtectionAttempt('protected_image_access', [
                'image_path' => $imagePath,
                'token_valid' => true
            ]);

            // Serve the image with appropriate headers
            return $this->serveProtectedImage($imagePath);
        }

        return $next($request);
    }

    /**
     * Check if request is rate limited
     */
    protected function isRateLimited(Request $request): bool
    {
        $key = 'protected_image_access:' . $request->ip();
        $maxAttempts = 100; // Max 100 requests per hour
        $decayMinutes = 60;

        $attempts = cache()->get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            return true;
        }

        cache()->put($key, $attempts + 1, now()->addMinutes($decayMinutes));
        return false;
    }

    /**
     * Serve protected image with appropriate headers
     */
    protected function serveProtectedImage(string $imagePath): Response
    {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return response('Not Found', 404);
            }

            $mimeType = mime_content_type($fullPath);
            $fileSize = filesize($fullPath);
            $lastModified = filemtime($fullPath);

            // Set security headers
            $headers = [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
                'Cache-Control' => 'private, max-age=3600', // Cache for 1 hour
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Content-Security-Policy' => "default-src 'none'; img-src 'self'",
            ];

            // Stream the file
            return response()->stream(function() use ($fullPath) {
                $stream = fopen($fullPath, 'rb');
                fpassthru($stream);
                fclose($stream);
            }, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Failed to serve protected image', [
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            
            return response('Internal Server Error', 500);
        }
    }
}