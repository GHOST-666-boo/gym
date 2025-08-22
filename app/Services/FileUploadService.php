<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    /**
     * Upload a file with error handling and validation.
     */
    public function uploadProductImage(UploadedFile $file, ?string $oldImagePath = null): array
    {
        try {
            // Validate file type and size
            $this->validateImageFile($file);
            
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Store the file
            $path = $file->storeAs('products', $filename, 'public');
            
            // Delete old image if provided and exists
            if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                try {
                    Storage::disk('public')->delete($oldImagePath);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete old image: {$oldImagePath}", ['error' => $e->getMessage()]);
                }
            }
            
            return [
                'success' => true,
                'path' => $path,
                'message' => 'Image uploaded successfully.'
            ];
            
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'path' => null,
                'message' => $this->getUploadErrorMessage($e)
            ];
        }
    }
    
    /**
     * Validate the uploaded image file.
     */
    protected function validateImageFile(UploadedFile $file): void
    {
        // Check file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('File size exceeds 5MB limit.');
        }
        
        // Check file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
        }
        
        // Check if file is actually an image
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new \Exception('Invalid image file.');
        }
        
        // Check image dimensions (optional - max 4000x4000)
        if ($imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
            throw new \Exception('Image dimensions too large. Maximum size is 4000x4000 pixels.');
        }
    }
    
    /**
     * Get user-friendly error message based on exception.
     */
    protected function getUploadErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();
        
        // Return specific validation messages
        if (str_contains($message, 'size exceeds') || 
            str_contains($message, 'Invalid file type') || 
            str_contains($message, 'Invalid image') ||
            str_contains($message, 'dimensions too large')) {
            return $message;
        }
        
        // Generic error for other issues
        return 'Failed to upload image. Please try again with a different file.';
    }
    
    /**
     * Delete a file safely.
     */
    public function deleteFile(string $path): bool
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to delete file: {$path}", ['error' => $e->getMessage()]);
            return false;
        }
    }
}