<?php

namespace App\Http\Controllers;

use App\Services\ImageProtectionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProtectedImageController extends Controller
{
    protected ImageProtectionService $protectionService;

    public function __construct(ImageProtectionService $protectionService)
    {
        $this->protectionService = $protectionService;
    }

    /**
     * Serve protected image
     */
    public function show(Request $request, string $token): Response
    {
        // Check if protection is enabled
        if (!$this->protectionService->isProtectionEnabled()) {
            return response('Protection not enabled', 404);
        }

        // Validate token
        $tokenData = $this->protectionService->validateImageToken($token);
        
        if (!$tokenData) {
            Log::warning('Invalid protected image token', [
                'token' => substr($token, 0, 20) . '...',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer')
            ]);
            
            // Return a generic error to avoid information disclosure
            return response('Not Found', 404);
        }

        $imagePath = $tokenData['path'];

        // Check if image exists
        if (!Storage::disk('public')->exists($imagePath)) {
            Log::warning('Protected image file not found', [
                'image_path' => $imagePath,
                'token' => substr($token, 0, 20) . '...',
                'ip' => $request->ip()
            ]);
            
            return response('Not Found', 404);
        }

        // Additional security checks
        if (!$this->validateImageAccess($request, $imagePath)) {
            Log::warning('Protected image access denied', [
                'image_path' => $imagePath,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer')
            ]);
            
            return response('Forbidden', 403);
        }

        // Log successful access
        Log::info('Protected image served successfully', [
            'image_path' => $imagePath,
            'token_valid' => true,
            'referer' => $request->header('referer'),
            'ip' => $request->ip()
        ]);

        // Serve the image
        return $this->serveImage($imagePath, $request);
    }

    /**
     * Validate image access with additional security checks
     */
    protected function validateImageAccess(Request $request, string $imagePath): bool
    {
        // Check referer if configured (optional security measure)
        $allowedReferers = config('image_protection.allowed_referers', []);
        if (!empty($allowedReferers)) {
            $referer = $request->header('referer');
            if (!$referer) {
                return false;
            }
            
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $allowed = false;
            
            foreach ($allowedReferers as $allowedReferer) {
                if (str_contains($refererHost, $allowedReferer)) {
                    $allowed = true;
                    break;
                }
            }
            
            if (!$allowed) {
                return false;
            }
        }

        // Check if image path is within allowed directories
        $allowedPaths = ['products/', 'uploads/', 'images/'];
        $pathAllowed = false;
        
        foreach ($allowedPaths as $allowedPath) {
            if (str_starts_with($imagePath, $allowedPath)) {
                $pathAllowed = true;
                break;
            }
        }
        
        if (!$pathAllowed) {
            Log::warning('Protected image access to disallowed path', [
                'image_path' => $imagePath,
                'allowed_paths' => $allowedPaths
            ]);
            return false;
        }

        return true;
    }

    /**
     * Serve image with appropriate headers and error handling
     */
    protected function serveImage(string $imagePath, Request $request): Response
    {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            
            // Final file existence check
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                Log::error('Protected image file not accessible', [
                    'image_path' => $imagePath,
                    'full_path' => $fullPath,
                    'exists' => file_exists($fullPath),
                    'readable' => is_readable($fullPath)
                ]);
                
                return response('Not Found', 404);
            }

            // Get file information
            $mimeType = mime_content_type($fullPath);
            $fileSize = filesize($fullPath);
            $lastModified = filemtime($fullPath);
            
            // Validate file type
            $allowedMimeTypes = [
                'image/jpeg',
                'image/png', 
                'image/gif',
                'image/webp',
                'image/svg+xml'
            ];
            
            if (!in_array($mimeType, $allowedMimeTypes)) {
                Log::warning('Protected image has invalid mime type', [
                    'image_path' => $imagePath,
                    'mime_type' => $mimeType,
                    'allowed_types' => $allowedMimeTypes
                ]);
                
                return response('Forbidden', 403);
            }

            // Check for conditional requests (304 Not Modified)
            $ifModifiedSince = $request->header('If-Modified-Since');
            if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
                return response('', 304);
            }

            // Set security and caching headers
            $headers = [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
                'ETag' => '"' . md5($imagePath . $lastModified) . '"',
                'Cache-Control' => 'private, max-age=3600, must-revalidate',
                'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
                
                // Security headers
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Content-Security-Policy' => "default-src 'none'; img-src 'self'",
                'X-Robots-Tag' => 'noindex, nofollow',
                
                // Prevent caching in shared caches
                'Pragma' => 'private',
                'Vary' => 'Accept-Encoding'
            ];

            // Handle range requests for large images
            $rangeHeader = $request->header('Range');
            if ($rangeHeader && str_starts_with($rangeHeader, 'bytes=')) {
                return $this->serveRangeRequest($fullPath, $rangeHeader, $headers);
            }

            // Stream the complete file
            return response()->stream(function() use ($fullPath) {
                $handle = fopen($fullPath, 'rb');
                if ($handle) {
                    while (!feof($handle)) {
                        echo fread($handle, 8192); // Read in 8KB chunks
                        flush();
                    }
                    fclose($handle);
                }
            }, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Failed to serve protected image', [
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response('Internal Server Error', 500);
        }
    }

    /**
     * Handle HTTP range requests for large images
     */
    protected function serveRangeRequest(string $filePath, string $rangeHeader, array $headers): Response
    {
        $fileSize = filesize($filePath);
        $ranges = $this->parseRangeHeader($rangeHeader, $fileSize);
        
        if (empty($ranges)) {
            $headers['Content-Range'] = "bytes */{$fileSize}";
            return response('Range Not Satisfiable', 416, $headers);
        }

        // For simplicity, only handle single range requests
        $range = $ranges[0];
        $start = $range['start'];
        $end = $range['end'];
        $length = $end - $start + 1;

        $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
        $headers['Content-Length'] = $length;
        $headers['Accept-Ranges'] = 'bytes';

        return response()->stream(function() use ($filePath, $start, $length) {
            $handle = fopen($filePath, 'rb');
            if ($handle) {
                fseek($handle, $start);
                $remaining = $length;
                
                while ($remaining > 0 && !feof($handle)) {
                    $chunkSize = min(8192, $remaining);
                    echo fread($handle, $chunkSize);
                    $remaining -= $chunkSize;
                    flush();
                }
                
                fclose($handle);
            }
        }, 206, $headers);
    }

    /**
     * Parse HTTP Range header
     */
    protected function parseRangeHeader(string $rangeHeader, int $fileSize): array
    {
        $ranges = [];
        $rangeHeader = str_replace('bytes=', '', $rangeHeader);
        $rangeSpecs = explode(',', $rangeHeader);

        foreach ($rangeSpecs as $rangeSpec) {
            $rangeSpec = trim($rangeSpec);
            
            if (str_contains($rangeSpec, '-')) {
                [$start, $end] = explode('-', $rangeSpec, 2);
                
                $start = $start === '' ? 0 : (int) $start;
                $end = $end === '' ? $fileSize - 1 : (int) $end;
                
                // Validate range
                if ($start >= 0 && $end < $fileSize && $start <= $end) {
                    $ranges[] = ['start' => $start, 'end' => $end];
                }
            }
        }

        return $ranges;
    }
}