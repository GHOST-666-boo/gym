<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * The file upload service instance.
     */
    protected FileUploadService $fileUploadService;

    /**
     * Create a new controller instance.
     */
    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Store multiple images for a product.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $uploadedImages = [];
        $errors = [];

        foreach ($request->file('images') as $index => $file) {
            try {
                $uploadResult = $this->fileUploadService->uploadProductImage($file);
                
                if ($uploadResult['success']) {
                    $productImage = ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $uploadResult['path'],
                        'alt_text' => $product->name . ' - Image ' . ($index + 1),
                        'sort_order' => ProductImage::where('product_id', $product->id)->max('sort_order') + 1,
                        'is_primary' => false, // Will be set automatically if it's the first image
                    ]);

                    $uploadedImages[] = [
                        'id' => $productImage->id,
                        'url' => $productImage->url,
                        'alt_text' => $productImage->alt_text,
                        'is_primary' => $productImage->is_primary,
                        'sort_order' => $productImage->sort_order,
                    ];
                } else {
                    $errors[] = "Failed to upload image " . ($index + 1) . ": " . $uploadResult['message'];
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to upload image " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => count($uploadedImages) > 0,
            'images' => $uploadedImages,
            'errors' => $errors,
            'message' => count($uploadedImages) . ' image(s) uploaded successfully.',
        ]);
    }

    /**
     * Update image details.
     */
    public function update(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
        }

        $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'is_primary' => 'boolean',
        ]);

        $image->update($request->only(['alt_text', 'is_primary']));

        return response()->json([
            'success' => true,
            'message' => 'Image updated successfully.',
            'image' => [
                'id' => $image->id,
                'url' => $image->url,
                'alt_text' => $image->alt_text,
                'is_primary' => $image->is_primary,
                'sort_order' => $image->sort_order,
            ],
        ]);
    }

    /**
     * Update image sort order.
     */
    public function updateOrder(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:product_images,id',
            'images.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->images as $imageData) {
            $image = ProductImage::where('id', $imageData['id'])
                ->where('product_id', $product->id)
                ->first();
                
            if ($image) {
                $image->update(['sort_order' => $imageData['sort_order']]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Image order updated successfully.',
        ]);
    }

    /**
     * Set an image as primary.
     */
    public function setPrimary(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
        }

        $image->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Primary image updated successfully.',
        ]);
    }

    /**
     * Delete an image.
     */
    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
        }

        try {
            // Delete the file
            $this->fileUploadService->deleteFile($image->image_path);
            
            // Delete the database record
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage(),
            ], 500);
        }
    }
}