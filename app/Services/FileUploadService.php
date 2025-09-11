<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    protected WatermarkService $watermarkService;

    public function __construct(WatermarkService $watermarkService)
    {
        $this->watermarkService = $watermarkService;
    }
    /**
     * Upload a single product image with error handling and validation.
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
            
            // Apply watermark to the uploaded image
            $watermarkedPath = $this->watermarkService->applyWatermark($path);
            
            // Delete old image if provided and exists
            if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                try {
                    // Delete both original and watermarked versions
                    Storage::disk('public')->delete($oldImagePath);
                    $this->watermarkService->deleteWatermarkedImage($oldImagePath);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete old image: {$oldImagePath}", ['error' => $e->getMessage()]);
                }
            }
            
            return [
                'success' => true,
                'path' => $watermarkedPath, // Return watermarked path
                'original_path' => $path,
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
     * Upload multiple product images with individual validation and error handling.
     * Maximum 10 images per product, each max 10MB.
     */
    public function uploadMultipleProductImages(array $files, int $productId): array
    {
        $results = [
            'success' => true,
            'uploaded' => [],
            'skipped' => [],
            'total_uploaded' => 0,
            'total_skipped' => 0,
            'messages' => []
        ];

        // Check current image count for this product
        $currentImageCount = \App\Models\ProductImage::where('product_id', $productId)->count();
        $remainingSlots = 10 - $currentImageCount;

        if ($remainingSlots <= 0) {
            return [
                'success' => false,
                'uploaded' => [],
                'skipped' => [],
                'total_uploaded' => 0,
                'total_skipped' => 0,
                'messages' => ['This product already has the maximum of 10 images.']
            ];
        }

        if (count($files) > $remainingSlots) {
            $results['messages'][] = "Only {$remainingSlots} more images can be added to this product.";
        }

        $processedCount = 0;
        $sortOrder = $currentImageCount + 1;

        foreach ($files as $index => $file) {
            if ($processedCount >= $remainingSlots) {
                $results['skipped'][] = [
                    'filename' => $file->getClientOriginalName(),
                    'reason' => 'Maximum 10 images per product limit reached'
                ];
                continue;
            }

            try {
                // Log file details for debugging
                Log::info('Processing file for upload', [
                    'filename' => $file->getClientOriginalName(),
                    'size_mb' => round($file->getSize() / 1024 / 1024, 2),
                    'mime_type' => $file->getMimeType(),
                    'product_id' => $productId
                ]);
                
                // Validate individual file
                $this->validateImageFile($file);
                
                // Generate unique filename
                $filename = time() . '_' . uniqid() . '_' . $index . '.' . $file->getClientOriginalExtension();
                
                // Store the file
                $path = $file->storeAs('products', $filename, 'public');
                
                // Create ProductImage record
                $productImage = \App\Models\ProductImage::create([
                    'product_id' => $productId,
                    'image_path' => $path,
                    'alt_text' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'sort_order' => $sortOrder,
                    'is_primary' => $currentImageCount === 0 && $processedCount === 0 // First image is primary
                ]);
                
                $results['uploaded'][] = [
                    'id' => $productImage->id,
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => $productImage->url,
                    'is_primary' => $productImage->is_primary
                ];
                
                $processedCount++;
                $sortOrder++;
                
            } catch (\Exception $e) {
                $results['skipped'][] = [
                    'filename' => $file->getClientOriginalName(),
                    'reason' => $this->getUploadErrorMessage($e)
                ];
                
                Log::error('Multiple file upload - individual file failed', [
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'product_id' => $productId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $results['total_uploaded'] = count($results['uploaded']);
        $results['total_skipped'] = count($results['skipped']);

        // Add summary message
        if ($results['total_uploaded'] > 0) {
            $results['messages'][] = "{$results['total_uploaded']} images uploaded successfully.";
        }
        
        if ($results['total_skipped'] > 0) {
            $results['messages'][] = "{$results['total_skipped']} images were skipped due to validation errors or limits.";
        }

        return $results;
    }
    
    /**
     * Validate the uploaded image file.
     */
    protected function validateImageFile(UploadedFile $file): void
    {
        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \Exception("File '{$file->getClientOriginalName()}' exceeds 10MB limit and will not be uploaded.");
        }
        
        // Check file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception("File '{$file->getClientOriginalName()}' has invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.");
        }
        
        // Check if file is actually an image
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new \Exception("File '{$file->getClientOriginalName()}' is not a valid image file.");
        }
        
        // Check image dimensions (optional - max 8000x8000 for high-res product images)
        if ($imageInfo[0] > 8000 || $imageInfo[1] > 8000) {
            throw new \Exception("Image '{$file->getClientOriginalName()}' dimensions too large. Maximum size is 8000x8000 pixels.");
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