<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Category;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
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
     * Display a listing of the products.
     */
    public function index(): View
    {
        $products = Product::with(['category', 'images'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            // Debug: Log what files are being received
            \Log::info('Product creation request debug', [
                'has_gallery_images' => $request->hasFile('gallery_images'),
                'has_image_path' => $request->hasFile('image_path'),
                'gallery_images_count' => $request->hasFile('gallery_images') ? count($request->file('gallery_images')) : 0,
                'all_files' => $request->allFiles()
            ]);

            // Remove image fields from validated data as we handle them separately
            unset($validated['image_path'], $validated['gallery_images']);

            $product = Product::create($validated);

            $messages = [];

            // Handle single image upload (legacy support)
            if ($request->hasFile('image_path')) {
                $uploadResult = $this->fileUploadService->uploadProductImage($request->file('image_path'));
                
                if ($uploadResult['success']) {
                    $product->update(['image_path' => $uploadResult['path']]);
                    $messages[] = 'Main image uploaded successfully.';
                } else {
                    $messages[] = 'Main image upload failed: ' . $uploadResult['message'];
                }
            }

            // Handle multiple images upload (gallery)
            if ($request->hasFile('gallery_images')) {
                $uploadResult = $this->fileUploadService->uploadMultipleProductImages(
                    $request->file('gallery_images'), 
                    $product->id
                );
                
                $messages = array_merge($messages, $uploadResult['messages']);
                
                if ($uploadResult['total_skipped'] > 0) {
                    $skippedDetails = [];
                    foreach ($uploadResult['skipped'] as $skipped) {
                        $skippedDetails[] = "{$skipped['filename']}: {$skipped['reason']}";
                    }
                    $messages[] = 'Skipped files: ' . implode('; ', $skippedDetails);
                }
            }

            $successMessage = "Product '{$product->name}' has been created successfully.";
            if (!empty($messages)) {
                $successMessage .= ' ' . implode(' ', $messages);
            }

            return redirect()
                ->route('admin.products.index')
                ->with('success', $successMessage);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create product. Please check your input and try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        $product->load('category');
        
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        try {
            $validated = $request->validated();

            // Remove image fields from validated data as we handle them separately
            unset($validated['image_path'], $validated['gallery_images']);

            $product->update($validated);

            $messages = [];

            // Handle single image upload (legacy support)
            if ($request->hasFile('image_path')) {
                $uploadResult = $this->fileUploadService->uploadProductImage(
                    $request->file('image_path'), 
                    $product->image_path
                );
                
                if ($uploadResult['success']) {
                    $product->update(['image_path' => $uploadResult['path']]);
                    $messages[] = 'Main image updated successfully.';
                } else {
                    $messages[] = 'Main image update failed: ' . $uploadResult['message'];
                }
            }

            // Handle multiple images upload (gallery)
            if ($request->hasFile('gallery_images')) {
                $uploadResult = $this->fileUploadService->uploadMultipleProductImages(
                    $request->file('gallery_images'), 
                    $product->id
                );
                
                $messages = array_merge($messages, $uploadResult['messages']);
                
                if ($uploadResult['total_skipped'] > 0) {
                    $skippedDetails = [];
                    foreach ($uploadResult['skipped'] as $skipped) {
                        $skippedDetails[] = "{$skipped['filename']}: {$skipped['reason']}";
                    }
                    $messages[] = 'Skipped files: ' . implode('; ', $skippedDetails);
                }
            }

            $successMessage = "Product '{$product->name}' has been updated successfully.";
            if (!empty($messages)) {
                $successMessage .= ' ' . implode(' ', $messages);
            }

            return redirect()
                ->route('admin.products.index')
                ->with('success', $successMessage);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update product. Please check your input and try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            $productName = $product->name;
            
            // Delete associated image if it exists
            if ($product->image_path) {
                $this->fileUploadService->deleteFile($product->image_path);
            }

            $product->delete();

            return redirect()
                ->route('admin.products.index')
                ->with('success', "Product '{$productName}' has been deleted successfully.");
                
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Failed to delete product. Please try again.');
        }
    }
}